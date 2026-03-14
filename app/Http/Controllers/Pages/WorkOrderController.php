<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ExternalEventLink;
use App\Models\Installer;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\GraphCalendarService;
use App\Services\GraphMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    // ── Installations group calendar ID ───────────────────────────
    const INSTALLATIONS_GROUP_ID = 'a6890136-56b9-42fc-ac2b-8e05c98c0e8c';

    // ── CRUD ──────────────────────────────────────────────────────

    public function create(Sale $sale)
    {
        $rooms = $sale->rooms()
            ->with(['items' => fn($q) => $q->where('item_type', 'labour')
                ->where('is_removed', false)
                ->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $installers    = Installer::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name', 'email', 'phone']);
        $scheduledQtys = $this->scheduledQtys($sale->id);

        return view('pages.work-orders.create', compact('sale', 'rooms', 'installers', 'scheduledQtys'));
    }

    public function store(Sale $sale, Request $request)
    {
        $data = $request->validate([
            'installer_id'   => ['nullable', 'integer', 'exists:installers,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'notes'          => ['nullable', 'string'],
            'items'          => ['nullable', 'array'],
            'qty'            => ['nullable', 'array'],
            'cost'           => ['nullable', 'array'],
        ]);

        $selectedItems = array_keys($request->input('items', []));

        if (empty($selectedItems)) {
            return back()->withInput()->withErrors(['items' => 'Please select at least one labour item.']);
        }

        // Validate qty doesn't exceed remaining per item
        $scheduledQtys = $this->scheduledQtys($sale->id);
        $saleItems     = SaleItem::whereIn('id', $selectedItems)->get()->keyBy('id');

        foreach ($selectedItems as $saleItemId) {
            $saleItem  = $saleItems[$saleItemId] ?? null;
            if (! $saleItem) continue;

            $qty       = (float) ($request->input("qty.{$saleItemId}") ?? $saleItem->quantity);
            $remaining = (float) $saleItem->quantity - (float) ($scheduledQtys[$saleItemId] ?? 0);

            if ($qty > $remaining) {
                return back()->withInput()->withErrors([
                    "qty.{$saleItemId}" => "Qty for \"{$saleItem->description}\" exceeds remaining ({$remaining} {$saleItem->unit}).",
                ]);
            }
        }

        // Auto-advance status
        $status = 'created';
        if (! empty($data['installer_id']) && ! empty($data['scheduled_date'])) {
            $status = 'scheduled';
        }

        DB::transaction(function () use ($sale, $data, $request, $selectedItems, $saleItems, $status, &$workOrder) {
            $workOrder = WorkOrder::create([
                'sale_id'        => $sale->id,
                'installer_id'   => $data['installer_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => $status,
            ]);

            $sortOrder = 0;
            foreach ($selectedItems as $saleItemId) {
                $saleItem = $saleItems[$saleItemId] ?? null;
                if (! $saleItem) continue;

                $qty  = (float) ($request->input("qty.{$saleItemId}") ?? $saleItem->quantity);
                $cost = (float) ($request->input("cost.{$saleItemId}") ?? $saleItem->cost_price);

                WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'sale_item_id'  => $saleItem->id,
                    'item_name'     => $this->buildItemName($saleItem),
                    'quantity'      => $qty,
                    'unit'          => $saleItem->unit,
                    'cost_price'    => $cost,
                    'sort_order'    => $sortOrder++,
                ]);
            }
        });

        $this->syncCalendarCreate($workOrder);

        return redirect()
            ->route('pages.sales.work-orders.show', [$sale, $workOrder])
            ->with('success', 'Work order ' . $workOrder->wo_number . ' created.');
    }

    public function show(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);
        $workOrder->load(['installer', 'items.saleItem', 'calendarEvent.externalLink', 'creator']);

        return view('pages.work-orders.show', compact('sale', 'workOrder'));
    }

    public function edit(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $workOrder->load(['installer', 'items.saleItem', 'calendarEvent.externalLink']);
        $installers = Installer::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name', 'email']);
        $maxQtys    = $this->maxQtys($workOrder);

        return view('pages.work-orders.edit', compact('sale', 'workOrder', 'installers', 'maxQtys'));
    }

    public function update(Sale $sale, WorkOrder $workOrder, Request $request)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $data = $request->validate([
            'installer_id'   => ['nullable', 'integer', 'exists:installers,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'notes'          => ['nullable', 'string'],
            'status'         => ['required', 'string', 'in:' . implode(',', WorkOrder::STATUSES)],
            'wo_items'       => ['nullable', 'array'],
            'wo_items.*.quantity'   => ['nullable', 'numeric', 'min:0'],
            'wo_items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Enforce: scheduled requires installer + date
        if ($data['status'] === 'scheduled' && $workOrder->status === 'created') {
            if (empty($data['installer_id']) || empty($data['scheduled_date'])) {
                return back()->withInput()->withErrors(['status' => 'Cannot mark as Scheduled without an installer and scheduled date.']);
            }
        }

        // Validate item qtys
        $maxQtys = $this->maxQtys($workOrder);
        foreach ($workOrder->items as $item) {
            $newQty = (float) ($request->input("wo_items.{$item->id}.quantity") ?? $item->quantity);
            $max    = $maxQtys[$item->id] ?? PHP_INT_MAX;
            if ($newQty > $max) {
                return back()->withInput()->withErrors([
                    "wo_items.{$item->id}.quantity" => "Qty for \"{$item->item_name}\" exceeds max allowed ({$max} {$item->unit}).",
                ]);
            }
        }

        // Detect calendar-relevant changes before update
        $calendarFieldsChanged =
            (int) ($data['installer_id'] ?? 0) !== (int) ($workOrder->installer_id ?? 0)
            || ($data['scheduled_date'] ?? '') !== ($workOrder->scheduled_date?->format('Y-m-d') ?? '')
            || ($data['scheduled_time'] ?? '') !== ($workOrder->scheduled_time ?? '');

        $wasCancelled   = $workOrder->status === 'cancelled';
        $beingCancelled = $data['status'] === 'cancelled';

        DB::transaction(function () use ($workOrder, $data, $request) {
            $workOrder->update([
                'installer_id'   => $data['installer_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => $data['status'],
            ]);

            foreach ($workOrder->items as $item) {
                $newQty  = $request->input("wo_items.{$item->id}.quantity");
                $newCost = $request->input("wo_items.{$item->id}.cost_price");

                if ($newQty !== null || $newCost !== null) {
                    $item->update([
                        'quantity'   => $newQty   ?? $item->quantity,
                        'cost_price' => $newCost  ?? $item->cost_price,
                    ]);
                }
            }
        });

        $workOrder->refresh();

        if (! $wasCancelled) {
            if ($beingCancelled) {
                $this->cancelCalendarEvent($workOrder);
            } elseif ($calendarFieldsChanged) {
                if ($workOrder->calendar_event_id) {
                    $this->syncCalendarUpdate($workOrder);
                } else {
                    $this->syncCalendarCreate($workOrder);
                }
            }
        }

        return redirect()
            ->route('pages.sales.work-orders.show', [$sale, $workOrder])
            ->with('success', 'Work order ' . $workOrder->wo_number . ' updated.');
    }

    public function destroy(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $this->cancelCalendarEvent($workOrder);
        $workOrder->delete();

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order deleted.');
    }

    // ── PDF ───────────────────────────────────────────────────────

    public function previewPdf(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);
        $workOrder->load(['installer', 'items', 'sale', 'creator']);

        $pdf      = Pdf::loadView('pdf.work-order', compact('workOrder'));
        $filename = $workOrder->wo_number . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // ── Email ─────────────────────────────────────────────────────

    public function sendEmail(Sale $sale, WorkOrder $workOrder, Request $request)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $workOrder->load(['installer', 'items', 'sale', 'creator']);

        $mailer     = app(GraphMailService::class);
        $pdfContent = Pdf::loadView('pdf.work-order', compact('workOrder'))->output();

        $attachment = [
            'filename' => $workOrder->wo_number . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        $sent = $mailer->send(
            $request->input('to'),
            $request->input('subject'),
            $request->input('body'),
            'work_order',
            null,
            $attachment,
        );

        if ($sent) {
            $workOrder->update(['sent_at' => now()]);
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send email. Check the mail log for details.');
        }

        return back()->with('success', 'Work order emailed to ' . $request->input('to') . '.');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Returns [sale_item_id => total_scheduled_qty] for all non-cancelled,
     * non-deleted WOs on a sale. Optionally excludes the given WO (for edit).
     */
    private function scheduledQtys(int $saleId, ?int $excludeWorkOrderId = null): array
    {
        $query = DB::table('work_order_items as woi')
            ->join('work_orders as wo', 'woi.work_order_id', '=', 'wo.id')
            ->where('wo.sale_id', $saleId)
            ->where('wo.status', '<>', 'cancelled')
            ->whereNull('wo.deleted_at');

        if ($excludeWorkOrderId) {
            $query->where('wo.id', '<>', $excludeWorkOrderId);
        }

        return $query->selectRaw('woi.sale_item_id, SUM(woi.quantity) as total_qty')
            ->groupBy('woi.sale_item_id')
            ->pluck('total_qty', 'sale_item_id')
            ->toArray();
    }

    /**
     * Returns [work_order_item_id => max_allowed_qty] for items on the given WO.
     * Max = sale_item.quantity - scheduled_by_other_wos
     */
    private function maxQtys(WorkOrder $workOrder): array
    {
        $scheduledByOthers = $this->scheduledQtys($workOrder->sale_id, $workOrder->id);
        $maxQtys           = [];

        foreach ($workOrder->items as $item) {
            $saleQty   = $item->saleItem ? (float) $item->saleItem->quantity : PHP_INT_MAX;
            $scheduled = (float) ($scheduledByOthers[$item->sale_item_id] ?? 0);
            $maxQtys[$item->id] = max(0, round($saleQty - $scheduled, 2));
        }

        return $maxQtys;
    }

    private function buildItemName(SaleItem $item): string
    {
        $parts = array_filter([$item->labour_type, $item->description]);
        return implode(' — ', $parts) ?: 'Labour Item';
    }

    // ── Calendar helpers ──────────────────────────────────────────

    private function buildEventData(WorkOrder $workOrder): array
    {
        $sale = $workOrder->relationLoaded('sale') ? $workOrder->sale : Sale::find($workOrder->sale_id);

        $date  = $workOrder->scheduled_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $time  = $workOrder->scheduled_time ?? '08:00';
        $start = Carbon::parse($date . ' ' . $time);
        $end   = $start->copy()->addHours(2);

        $title = $workOrder->wo_number;
        if ($sale->job_name) {
            $title = $workOrder->wo_number . ' · ' . $sale->job_name;
        }

        $notesParts = [];
        if ($sale->sale_number) {
            $notesParts[] = 'Sale: ' . $sale->sale_number;
        }
        if ($workOrder->installer) {
            $notesParts[] = 'Installer: ' . $workOrder->installer->company_name;
        }
        $itemSummary = $workOrder->items->map(fn($i) => $i->item_name . ' (' . $i->quantity . ' ' . $i->unit . ')')->implode(', ');
        if ($itemSummary) {
            $notesParts[] = 'Work: ' . $itemSummary;
        }
        if ($workOrder->notes) {
            $notesParts[] = "\nNotes:\n" . $workOrder->notes;
        }

        return [
            'title'    => $title,
            'start'    => $start,
            'end'      => $end,
            'location' => $sale->job_address ?? null,
            'notes'    => implode("\n", $notesParts),
        ];
    }

    /**
     * Create a calendar event on RM – Installations group calendar (best-effort).
     * Uses the currently logged-in user's MS account to write to the group calendar.
     */
    private function syncCalendarCreate(WorkOrder $workOrder): void
    {
        if (empty($workOrder->installer_id) || empty($workOrder->scheduled_date)) {
            return;
        }

        try {
            $workOrder->loadMissing(['installer', 'items', 'sale']);

            $account = MicrosoftAccount::where('user_id', auth()->id())
                ->where('is_connected', true)
                ->first();

            if (! $account) {
                Log::info('[WO] No connected Microsoft account for current user — skipping calendar', ['wo_id' => $workOrder->id]);
                return;
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('group_id', self::INSTALLATIONS_GROUP_ID)
                ->first();

            if (! $calendar) {
                Log::warning('[WO] RM–Installations calendar not found for account — skipping', [
                    'wo_id'      => $workOrder->id,
                    'account_id' => $account->id,
                ]);
                return;
            }

            $eventData  = $this->buildEventData($workOrder);
            $service    = new GraphCalendarService();
            $externalId = $service->createEvent($account, $calendar, $eventData);
            $localEvent = $service->persistLocalEvent(
                $account,
                $calendar,
                $externalId,
                $eventData,
                WorkOrder::class,
                $workOrder->id
            );

            $workOrder->update(['calendar_event_id' => $localEvent->id]);

            Log::info('[WO] Calendar event created on RM–Installations', [
                'wo_id'             => $workOrder->id,
                'calendar_event_id' => $localEvent->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event creation failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncCalendarUpdate(WorkOrder $workOrder): void
    {
        if (empty($workOrder->calendar_event_id)) {
            return;
        }

        try {
            $workOrder->loadMissing(['installer', 'items', 'sale', 'calendarEvent.externalLink']);

            $link = $workOrder->calendarEvent?->externalLink;
            if (! $link) {
                Log::warning('[WO] No ExternalEventLink found for update — skipping', ['wo_id' => $workOrder->id]);
                return;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if (! $account) return;

            $eventData = $this->buildEventData($workOrder);
            $service   = new GraphCalendarService();
            $service->updateEvent($account, $link, $eventData);

            $workOrder->calendarEvent?->update([
                'title'       => $eventData['title'],
                'starts_at'   => $eventData['start'],
                'ends_at'     => $eventData['end'],
                'location'    => $eventData['location'],
                'description' => $eventData['notes'],
            ]);

            Log::info('[WO] Calendar event updated', ['wo_id' => $workOrder->id]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event update failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function cancelCalendarEvent(WorkOrder $workOrder): void
    {
        if (empty($workOrder->calendar_event_id)) {
            return;
        }

        try {
            $workOrder->loadMissing(['calendarEvent.externalLink']);

            $link = $workOrder->calendarEvent?->externalLink;
            if (! $link) return;

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if ($account) {
                $service = new GraphCalendarService();
                $service->deleteEvent($account, $link);
            }

            $workOrder->calendarEvent?->delete();
            $workOrder->update(['calendar_event_id' => null]);

            Log::info('[WO] Calendar event deleted', ['wo_id' => $workOrder->id]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event deletion failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
