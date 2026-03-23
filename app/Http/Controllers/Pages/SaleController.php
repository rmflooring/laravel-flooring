<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReturnItem;
use App\Models\Sale;
use App\Models\Employee;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class SaleController extends Controller
{
	
	public function index(Request $request)
	{
		$q        = trim((string) $request->get('q', ''));
		$status   = trim((string) $request->get('status', ''));
		$dateFrom = trim((string) $request->get('date_from', ''));
		$dateTo   = trim((string) $request->get('date_to', ''));
		$hasCo    = $request->boolean('has_co', false);

		$statusOptions = [
			'open',
			'sent',
			'approved',
			'change_in_progress',
			'scheduled',
			'in_progress',
			'on_hold',
			'completed',
			'partially_invoiced',
			'invoiced',
			'cancelled',
		];

		$query = Sale::query()->latest('id');

		// Status filter
		if ($status !== '') {
			$query->where('status', $status);
		}

		// Has change orders filter
		if ($hasCo) {
			$query->whereHas('changeOrders');
		}

		// Date range filters (created_at)
		if ($dateFrom !== '') {
			$query->whereDate('created_at', '>=', $dateFrom);
		}
		if ($dateTo !== '') {
			$query->whereDate('created_at', '<=', $dateTo);
		}

		// Search — includes CO number via subquery
		if ($q !== '') {
			$table = (new Sale())->getTable();

			$searchableCols = [
				'id',
				'source_estimate_number',
				'status',
				'job_name',
				'job_no',
				'customer_name',
				'pm_name',
			];

			$existingCols = array_values(array_filter($searchableCols, fn ($col) =>
				$col === 'id' || Schema::hasColumn($table, $col)
			));

			$query->where(function ($qq) use ($q, $existingCols) {
				if (ctype_digit($q) && in_array('id', $existingCols, true)) {
					$qq->orWhere('id', (int) $q);
				}

				foreach ($existingCols as $col) {
					if ($col === 'id') continue;
					$qq->orWhere($col, 'like', "%{$q}%");
				}

				// CO number search (e.g. "CO-1-3")
				$qq->orWhereHas('changeOrders', fn ($cq) =>
					$cq->where('co_number', 'like', "%{$q}%")
				);
			});
		}

		$sales = $query
            ->withCount(['purchaseOrders', 'workOrders', 'changeOrders'])
            ->with(['changeOrders' => fn($q) => $q->whereIn('status', ['draft', 'sent'])->limit(1)])
            ->paginate(25)->withQueryString();

		return view('pages.sales.index', compact(
			'sales',
			'q',
			'status',
			'dateFrom',
			'dateTo',
			'hasCo',
			'statusOptions'
		));
	}
	
	public function show(Sale $sale)
	{
		$sale->load([
			'sourceEstimate',
			'salesperson1Employee',
			'opportunity.projectManager',
			'rooms' => fn($q) => $q->orderBy('sort_order'),
			'rooms.items' => fn($q) => $q->where('is_removed', false)->orderBy('sort_order'),
			'purchaseOrders.vendor',
			'purchaseOrders.items',
			'workOrders.installer',
			'workOrders.items',
			'changeOrders',
		]);
		[$emailSubject, $emailBody] = $this->resolveEmailTemplate($sale);
		$itemPoStatusMap = $this->buildItemPoStatusMap($sale);
		$itemWoStatusMap = $this->buildItemWoStatusMap($sale);
		$pmEmail         = $sale->opportunity?->projectManager?->email;
		return view('pages.sales.show', compact('sale', 'emailSubject', 'emailBody', 'itemPoStatusMap', 'itemWoStatusMap', 'pmEmail'));
	}

    public function edit(Sale $sale)
    {
        $sale->load([
			'creator',
			'updater',
            'rooms' => function ($q) { $q->orderBy('sort_order'); },
            'rooms.items' => function ($q) { $q->orderBy('sort_order'); },
            'rooms.items.productStyle',
            'sourceEstimate',
            'salesperson1Employee',
            'opportunity.projectManager',
            'purchaseOrders.vendor',
            'purchaseOrders.items',
            'workOrders.installer',
            'workOrders.items',
        ]);

        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();

        $taxGroups = DB::table('tax_rate_groups')
            ->select('tax_rate_groups.*')
            ->whereNull('tax_rate_groups.deleted_at')
            ->orderBy('tax_rate_groups.name')
            ->get();

        $defaultTaxGroupId = DB::table('default_tax')->where('id', 1)->value('tax_rate_group_id');

        [$emailSubject, $emailBody] = $this->resolveEmailTemplate($sale);
        $itemPoStatusMap = $this->buildItemPoStatusMap($sale);
        $itemWoStatusMap = $this->buildItemWoStatusMap($sale);
        $pmEmail         = $sale->opportunity?->projectManager?->email;

        return view('pages.sales.edit', compact(
            'sale', 'employees', 'taxGroups', 'defaultTaxGroupId',
            'emailSubject', 'emailBody', 'itemPoStatusMap', 'itemWoStatusMap', 'pmEmail',
        ));
    }
	
public function update(\Illuminate\Http\Request $request, \App\Models\Sale $sale)
{
    $data = $request->validate([
        'parent_customer_name' => ['nullable', 'string', 'max:255'],
        'homeowner_name'       => ['nullable', 'string', 'max:255'],
        'homeowner_phone'      => ['nullable', 'string', 'max:50'],
        'homeowner_email'      => ['nullable', 'email', 'max:255'],
        'pm_name'              => ['nullable', 'string', 'max:255'],
        'job_number'           => ['nullable', 'string', 'max:255'],
        'job_name'             => ['nullable', 'string', 'max:255'],
        'job_address'          => ['nullable', 'string', 'max:255'],
        'notes'                => ['nullable', 'string'],

        // totals (your estimate blade likely posts these)
        'subtotal_materials' => ['nullable', 'numeric'],
        'subtotal_labour'    => ['nullable', 'numeric'],
        'subtotal_freight'   => ['nullable', 'numeric'],
        'pretax_total'       => ['nullable', 'numeric'],
        'tax_group_id'       => ['nullable', 'integer'],
        'tax_rate_percent'   => ['nullable', 'numeric'],
        'tax_amount'         => ['nullable', 'numeric'],
        'grand_total'        => ['nullable', 'numeric'],

        'status'             => ['nullable', 'in:open,sent,approved,scheduled,in_progress,on_hold,completed,partially_invoiced,invoiced,cancelled'],

        'rooms'              => ['nullable', 'array'],
        'rooms.*.id'         => ['nullable', 'integer'],
        'rooms.*.room_name'  => ['nullable', 'string', 'max:255'],

        'rooms.*.materials'  => ['nullable', 'array'],
        'rooms.*.freight'    => ['nullable', 'array'],
        'rooms.*.labour'     => ['nullable', 'array'],
    ]);

    \DB::transaction(function () use ($sale, $data) {

        // 1) Header
        $sale->fill([
            'customer_name'      => $data['parent_customer_name'] ?? $sale->customer_name,
            'homeowner_name'     => $data['homeowner_name'] ?? $sale->homeowner_name,
            'job_phone'          => $data['homeowner_phone'] ?? $sale->job_phone,
            'job_email'          => $data['homeowner_email'] ?? $sale->job_email,
            'status'             => $data['status'] ?? $sale->status,
            'pm_name'            => $data['pm_name'] ?? $sale->pm_name,
            'job_no'             => $data['job_number'] ?? $sale->job_no,
            'job_name'           => $data['job_name'] ?? $sale->job_name,
            'job_address'        => $data['job_address'] ?? $sale->job_address,
            'notes'              => $data['notes'] ?? $sale->notes,

            'subtotal_materials' => (float)($data['subtotal_materials'] ?? $sale->subtotal_materials ?? 0),
            'subtotal_labour'    => (float)($data['subtotal_labour'] ?? $sale->subtotal_labour ?? 0),
            'subtotal_freight'   => (float)($data['subtotal_freight'] ?? $sale->subtotal_freight ?? 0),
            'pretax_total'       => (float)($data['pretax_total'] ?? $sale->pretax_total ?? 0),

            'tax_group_id'       => $data['tax_group_id'] ?? $sale->tax_group_id,
            'tax_rate_percent'   => (float)($data['tax_rate_percent'] ?? $sale->tax_rate_percent ?? 0),
            'tax_amount'         => (float)($data['tax_amount'] ?? $sale->tax_amount ?? 0),
            'grand_total'        => (float)($data['grand_total'] ?? $sale->grand_total ?? 0),

            'updated_by'         => auth()->id(),
        ])->save();

        $rooms = $data['rooms'] ?? [];

        // 2) Delete rooms removed in UI
        $existingRoomIds = $sale->rooms()->pluck('id')->all();

        $submittedRoomIds = collect($rooms)
            ->pluck('id')
            ->filter()
            ->map(function ($v) { return (int)$v; })
            ->all();

        $roomIdsToDelete = array_values(array_diff($existingRoomIds, $submittedRoomIds));

        if (!empty($roomIdsToDelete)) {
            \App\Models\SaleItem::where('sale_id', $sale->id)
                ->whereIn('sale_room_id', $roomIdsToDelete)
                ->delete();

            \App\Models\SaleRoom::where('sale_id', $sale->id)
                ->whereIn('id', $roomIdsToDelete)
                ->delete();
        }

        // 3) Upsert rooms + replace items
        foreach ($rooms as $roomIndex => $roomData) {

            $roomId = $roomData['id'] ?? null;

            if ($roomId) {
                $room = \App\Models\SaleRoom::where('id', (int)$roomId)
                    ->where('sale_id', $sale->id)
                    ->firstOrFail();
            } else {
                $room = new \App\Models\SaleRoom();
                $room->sale_id = $sale->id;
            }

            $room->room_name  = $roomData['room_name'] ?? null;
            $room->sort_order = (int)$roomIndex;

            // If your blade posts room subtotals, we’ll keep them. If not, they’ll stay 0 for now.
            $room->subtotal_materials = (float)($roomData['subtotal_materials'] ?? $room->subtotal_materials ?? 0);
            $room->subtotal_labour    = (float)($roomData['subtotal_labour'] ?? $room->subtotal_labour ?? 0);
            $room->subtotal_freight   = (float)($roomData['subtotal_freight'] ?? $room->subtotal_freight ?? 0);
            $room->room_total         = (float)($roomData['room_total'] ?? $room->room_total ?? 0);

            $room->save();

            $saleRoomId = $room->id;

            // Replace items for this room
            \App\Models\SaleItem::where('sale_id', $sale->id)
                ->where('sale_room_id', $saleRoomId)
                ->delete();

            // MATERIALS
            foreach (($roomData['materials'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['product_type', 'quantity', 'sell_price'])) continue;

                \App\Models\SaleItem::create([
                    'sale_id'          => $sale->id,
                    'sale_room_id'     => $saleRoomId,
                    'item_type'        => 'material',
                    'sort_order'       => (int)$i,

                    'product_type'     => $item['product_type'] ?? null,
                    'product_line_id'  => ($item['product_line_id'] ?? '') !== '' ? (int)$item['product_line_id'] : null,
                    'product_style_id' => ($item['product_style_id'] ?? '') !== '' ? (int)$item['product_style_id'] : null,
                    'manufacturer'     => $item['manufacturer'] ?? null,
                    'style'            => $item['style'] ?? null,
                    'color_item_number'=> $item['color_item_number'] ?? null,
                    'po_notes'         => $item['po_notes'] ?? null,

                    'quantity'         => (float)($item['quantity'] ?? 0),
                    'order_qty'        => ($item['order_qty'] ?? '') !== '' ? (float)$item['order_qty'] : null,
                    'unit'             => $item['unit'] ?? null,
					'cost_price'        => (float)($item['cost_price'] ?? 0),
					'cost_total'        => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'       => (float)($item['sell_price'] ?? 0),
                    'line_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),

                    'notes'            => $item['notes'] ?? null,
                ]);
            }

            // FREIGHT
            foreach (($roomData['freight'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;

                \App\Models\SaleItem::create([
                    'sale_id'            => $sale->id,
                    'sale_room_id'       => $saleRoomId,
                    'item_type'          => 'freight',
                    'sort_order'         => (int)$i,

                    'freight_description'=> $item['freight_description'] ?? null,
                    'quantity'           => (float)($item['quantity'] ?? 0),
                    'unit'               => $item['unit'] ?? null,
					'cost_price'       => (float)($item['cost_price'] ?? 0),
					'cost_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'         => (float)($item['sell_price'] ?? 0),
                    'line_total'         => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),

                    'notes'              => $item['notes'] ?? null,
                ]);
            }

            // LABOUR
            foreach (($roomData['labour'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['labour_type', 'description', 'quantity', 'sell_price'])) continue;

                \App\Models\SaleItem::create([
                    'sale_id'      => $sale->id,
                    'sale_room_id' => $saleRoomId,
                    'item_type'    => 'labour',
                    'sort_order'   => (int)$i,

                    'labour_type'  => $item['labour_type'] ?? null,
                    'description'  => $item['description'] ?? null,
                    'quantity'     => (float)($item['quantity'] ?? 0),
                    'order_qty'    => ($item['order_qty'] ?? '') !== '' ? (float)$item['order_qty'] : null,
                    'unit'         => $item['unit'] ?? null,
					'cost_price'       => (float)($item['cost_price'] ?? 0),
					'cost_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'   => (float)($item['sell_price'] ?? 0),
                    'line_total'   => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),

                    'notes'        => $item['notes'] ?? null,
                ]);
            }
        }
    });

    return back()->with('success', 'Sale updated.');
}

	public function saveProfitCosts(\Illuminate\Http\Request $request, \App\Models\Sale $sale)
{
    $data = $request->validate([
        'items' => ['required', 'array'],
        'items.*.id' => ['required', 'integer'],
        'items.*.cost_price' => ['nullable', 'numeric'],
    ]);

    \DB::transaction(function () use ($sale, $data) {
        foreach ($data['items'] as $row) {
            $item = \App\Models\SaleItem::where('sale_id', $sale->id)
                ->where('id', (int) $row['id'])
                ->first();

            if (!$item) {
                continue;
            }

            $costPrice = (float) ($row['cost_price'] ?? 0);
            $qty = (float) ($item->quantity ?? 0);

            $item->cost_price = $costPrice;
            $item->cost_total = round($qty * $costPrice, 2);
            $item->save();
        }
    });

    return redirect()
    ->route('pages.sales.profits.show', $sale->id)
    ->with('success', 'Profit costs saved successfully.');
}
	
private function isRowEmpty(array $row, array $keysToCheck): bool
{
    foreach ($keysToCheck as $key) {
        if (!empty($row[$key])) return false;
    }
    return true;
}

public function destroy(Sale $sale)
{
    if ($sale->purchaseOrders()->exists()) {
        return back()->with('error', 'This sale cannot be deleted because it has purchase orders associated with it.');
    }

    if ($sale->workOrders()->exists()) {
        return back()->with('error', 'This sale cannot be deleted because it has work orders associated with it.');
    }

    $sale->delete();

    return redirect()->route('pages.sales.index')->with('success', 'Sale #' . $sale->sale_number . ' deleted.');
}

public function showProfits(Sale $sale)
{
    $sale->load([
        'rooms.items.productStyle',
        'rooms.items.sourceEstimateItem.productStyle',
    ]);

    $saleItemIds = $sale->rooms->flatMap->items->pluck('id');

    $vendorCredits = InventoryReturnItem::whereIn('sale_item_id', $saleItemIds)
        ->where('apply_to_sale_cost', true)
        ->whereNotNull('cost_applied_at')
        ->with(['inventoryReturn', 'saleItem'])
        ->get();

    return view('pages.profits.show', [
        'recordType'    => 'sale',
        'record'        => $sale,
        'rooms'         => $sale->rooms,
        'vendorCredits' => $vendorCredits,
    ]);
}

    public function previewPdf(Sale $sale)
    {
        $sale->loadMissing(['rooms.items', 'sourceEstimate']);
        $pdf = Pdf::loadView('pdf.sale', compact('sale'));
        $filename = 'Sale-' . ($sale->sale_number ?? $sale->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function sendEmail(Request $request, Sale $sale)
    {
        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
            'cc'      => ['nullable', 'array'],
            'cc.*'    => ['nullable', 'email'],
        ]);

        $user   = auth()->user();
        $mailer = app(GraphMailService::class);
        $cc     = array_filter($request->input('cc', []));

        $sale->loadMissing(['rooms.items', 'sourceEstimate']);
        $pdfContent = Pdf::loadView('pdf.sale', compact('sale'))->output();
        $attachment = [
            'filename' => 'Sale-' . ($sale->sale_number ?? $sale->id) . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $request->input('to'), $request->input('subject'), $request->input('body'), 'sale', $attachment, $cc ?: null)
            : false;

        if (! $sent) {
            $sent = $mailer->send($request->input('to'), $request->input('subject'), $request->input('body'), 'sale', null, $attachment, $cc ?: null);
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send sale email. Check the mail log for details.');
        }

        $sale->update(['status' => 'sent']);

        return back()->with('success', 'Sale emailed to ' . $request->input('to') . ' and status updated to Sent.');
    }

    private function resolveEmailTemplate(Sale $sale): array
    {
        $user            = auth()->user();
        $templateService = app(EmailTemplateService::class);
        $template        = $templateService->getTemplate($user, 'sale');

        $vars = [
            'customer_name'    => $sale->sourceEstimate?->homeowner_name ?: $sale->customer_name,
            'sale_number'      => $sale->sale_number,
            'grand_total'      => '$' . number_format((float) $sale->grand_total, 2),
            'job_name'         => $sale->job_name,
            'job_no'           => $sale->job_no,
            'job_address'      => $sale->job_address,
            'pm_name'          => $sale->pm_name,
            'pm_first_name'    => explode(' ', trim($sale->pm_name ?? ''))[0],
            'salesperson_name' => $sale->salesperson1Employee?->first_name
                ? $sale->salesperson1Employee->first_name . ' ' . $sale->salesperson1Employee->last_name
                : $user->name,
            'sender_name'      => $user->name,
            'sender_email'     => $user->email,
        ];

        return [
            $templateService->render($template['subject'], $vars),
            $templateService->render($template['body'], $vars),
        ];
    }

    /**
     * Build a map of sale_item_id => color status for qty highlighting.
     *
     * green  = all sale item qty is covered by received POs
     * yellow = some qty ordered or partially received, but not fully received
     * orange = PO exists but nothing ordered yet (all pending)
     */
    private function buildItemPoStatusMap(\App\Models\Sale $sale): array
    {
        // Collect qty by status per sale item
        $qtyByStatus = []; // [sale_item_id][status] => total qty

        foreach ($sale->purchaseOrders->where('status', '<>', 'cancelled') as $po) {
            foreach ($po->items as $poItem) {
                if (! $poItem->sale_item_id) continue;
                $id = $poItem->sale_item_id;
                $qtyByStatus[$id][$po->status] = ($qtyByStatus[$id][$po->status] ?? 0) + (float) $poItem->quantity;
            }
        }

        // Build sale item effective-qty map (order_qty if set, else quantity)
        $saleItemQtys = [];
        foreach ($sale->rooms as $room) {
            foreach ($room->items as $item) {
                $saleItemQtys[$item->id] = $item->order_qty !== null
                    ? (float) $item->order_qty
                    : (float) $item->quantity;
            }
        }

        $map = [];
        foreach ($qtyByStatus as $saleItemId => $statuses) {
            $deliveredQty = $statuses['delivered'] ?? 0;
            $receivedQty  = $statuses['received']  ?? 0;
            $orderedQty   = $statuses['ordered']   ?? 0;
            $saleQty      = $saleItemQtys[$saleItemId] ?? 0;

            if ($saleQty > 0 && $deliveredQty >= $saleQty) {
                $map[$saleItemId] = 'delivered'; // fully delivered to site → dark teal
            } elseif ($saleQty > 0 && $receivedQty >= $saleQty) {
                $map[$saleItemId] = 'received';  // fully received at warehouse → light green
            } elseif ($orderedQty > 0 || $receivedQty > 0 || $deliveredQty > 0) {
                $map[$saleItemId] = 'ordered';   // partially ordered/received → yellow
            } else {
                $map[$saleItemId] = 'pending';   // only pending POs → orange
            }
        }

        return $map;
    }

    /**
     * Build a map of sale_item_id => color status for labour WO qty highlighting.
     *
     * green  = all sale item qty is covered by completed WOs
     * yellow = WO is scheduled or in_progress (or partially completed)
     * orange = WO created but not yet scheduled
     */
    private function buildItemWoStatusMap(\App\Models\Sale $sale): array
    {
        $qtyByStatus = []; // [sale_item_id][status] => total qty

        foreach ($sale->workOrders->where('status', '<>', 'cancelled') as $wo) {
            foreach ($wo->items as $woItem) {
                if (! $woItem->sale_item_id) continue;
                $id = $woItem->sale_item_id;
                $qtyByStatus[$id][$wo->status] = ($qtyByStatus[$id][$wo->status] ?? 0) + (float) $woItem->quantity;
            }
        }

        $saleItemQtys = [];
        foreach ($sale->rooms as $room) {
            foreach ($room->items as $item) {
                $saleItemQtys[$item->id] = $item->order_qty !== null
                    ? (float) $item->order_qty
                    : (float) $item->quantity;
            }
        }

        $map = [];
        foreach ($qtyByStatus as $saleItemId => $statuses) {
            $completedQty  = $statuses['completed']   ?? 0;
            $inProgressQty = $statuses['in_progress'] ?? 0;
            $scheduledQty  = $statuses['scheduled']   ?? 0;
            $saleQty       = $saleItemQtys[$saleItemId] ?? 0;

            if ($saleQty > 0 && $completedQty >= $saleQty) {
                $map[$saleItemId] = 'completed';  // fully completed → green
            } elseif ($inProgressQty > 0 || $scheduledQty > 0 || $completedQty > 0) {
                $map[$saleItemId] = 'scheduled';  // scheduled/in_progress/partially done → yellow
            } else {
                $map[$saleItemId] = 'created';    // only created WOs → orange
            }
        }

        return $map;
    }

}
