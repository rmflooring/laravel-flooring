<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ExternalEventLink;
use App\Models\Installer;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Bill;
use App\Models\PickTicket;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemMaterial;
use App\Models\Setting;
use App\Services\CalendarTemplateService;
use App\Services\EmailTemplateService;
use App\Services\GraphCalendarService;
use App\Services\GraphMailService;
use App\Services\ICalService;
use App\Services\PickTicketService;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    // ── Installations group calendar ID ───────────────────────────
    const INSTALLATIONS_GROUP_ID = 'a6890136-56b9-42fc-ac2b-8e05c98c0e8c';

    // ── Index — all work orders with search/filters ───────────────

    public function index(Request $request)
    {
        $q        = trim($request->input('q', ''));
        $status   = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');

        $workOrders = WorkOrder::with(['installer', 'sale', 'items'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('wo_number', 'like', "%{$q}%")
                        ->orWhereHas('installer', fn ($iq) => $iq->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('sale', fn ($sq) => $sq->where('sale_number', 'like', "%{$q}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $statusOptions = WorkOrder::STATUSES;

        return view('pages.work-orders.index', compact(
            'workOrders', 'statusOptions', 'q', 'status', 'dateFrom', 'dateTo'
        ));
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function create(Sale $sale)
    {
        if ($sale->status === 'change_in_progress') {
            return redirect()
                ->route('pages.sales.show', $sale)
                ->with('error', 'This sale has an active Change Order. Work Orders cannot be created until the Change Order is approved or cancelled.');
        }

        $rooms = $sale->rooms()
            ->with(['items' => fn($q) => $q->whereIn('item_type', ['labour', 'material'])
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
        if ($sale->status === 'change_in_progress') {
            return redirect()
                ->route('pages.sales.show', $sale)
                ->with('error', 'This sale has an active Change Order. Work Orders cannot be created until it is resolved.');
        }

        $data = $request->validate([
            'installer_id'   => ['nullable', 'integer', 'exists:installers,id'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'notes'          => ['nullable', 'string'],
            'items'          => ['nullable', 'array'],
            'qty'            => ['nullable', 'array'],
            'cost'           => ['nullable', 'array'],
            'wo_notes'       => ['nullable', 'array'],
            'wo_notes.*'     => ['nullable', 'string'],
            'materials'      => ['nullable', 'array'],
            'materials.*'    => ['nullable', 'array'],
            'materials.*.*'  => ['nullable', 'integer', 'exists:sale_items,id'],
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

            $effectiveQty = $saleItem->order_qty !== null ? (float) $saleItem->order_qty : (float) $saleItem->quantity;
            $qty       = (float) ($request->input("qty.{$saleItemId}") ?? $effectiveQty);
            $remaining = $effectiveQty - (float) ($scheduledQtys[$saleItemId] ?? 0);

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

                $qty     = (float) ($request->input("qty.{$saleItemId}") ?? $saleItem->quantity);
                $cost    = (float) ($request->input("cost.{$saleItemId}") ?? $saleItem->cost_price);
                $woNotes = $request->input("wo_notes.{$saleItemId}") ?: null;

                $woItem = WorkOrderItem::create([
                    'work_order_id' => $workOrder->id,
                    'sale_item_id'  => $saleItem->id,
                    'item_name'     => $this->buildItemName($saleItem),
                    'quantity'      => $qty,
                    'unit'          => $saleItem->unit,
                    'wo_notes'      => $woNotes,
                    'cost_price'    => $cost,
                    'sort_order'    => $sortOrder++,
                ]);

                $materialIds = $request->input("materials.{$saleItemId}", []);
                foreach ($materialIds as $matId) {
                    WorkOrderItemMaterial::create([
                        'work_order_item_id' => $woItem->id,
                        'sale_item_id'       => (int) $matId,
                    ]);
                }
            }
        });

        if ($request->boolean('sync_calendar', false)) {
            $this->syncCalendarCreate($workOrder);
        }

        if ($status === 'scheduled') {
            $this->sendScheduledSms($workOrder);
        }

        return redirect()
            ->route('pages.sales.work-orders.show', [$sale, $workOrder])
            ->with('success', 'Work order ' . $workOrder->wo_number . ' created.');
    }

    public function show(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);
        $sale->loadMissing(['sourceEstimate', 'opportunity.parentCustomer.contacts']);
        $workOrder->load(['installer', 'items.relatedMaterials.saleItem', 'items.saleItem.room', 'calendarEvent.externalLink', 'creator']);

        // Load the active staging pick ticket for this WO (if any)
        $stagingPickTicket = PickTicket::where('work_order_id', $workOrder->id)
            ->whereNotIn('status', ['cancelled'])
            ->with(['items.saleItem.room', 'creator'])
            ->first();

        // Compute stock warnings for the stage modal (only when staging is possible)
        $materialWarnings = collect();
        if (! $stagingPickTicket) {
            $materialSaleItems = $workOrder->items
                ->flatMap(fn ($item) => $item->relatedMaterials)
                ->map(fn ($mat) => $mat->saleItem)
                ->filter()
                ->unique('id')
                ->values();

            if ($materialSaleItems->isNotEmpty()) {
                $allocatedQtys = \App\Models\InventoryAllocation::whereIn('sale_item_id', $materialSaleItems->pluck('id'))
                    ->selectRaw('sale_item_id, SUM(quantity) as total')
                    ->groupBy('sale_item_id')
                    ->pluck('total', 'sale_item_id')
                    ->map(fn ($v) => (float) $v);

                $materialWarnings = $materialSaleItems->filter(function ($saleItem) use ($allocatedQtys) {
                    return ($allocatedQtys[$saleItem->id] ?? 0.0) < (float) $saleItem->quantity;
                })->map(function ($saleItem) use ($allocatedQtys) {
                    $allocated = $allocatedQtys[$saleItem->id] ?? 0.0;
                    $name = implode(' — ', array_filter([
                        $saleItem->product_type, $saleItem->manufacturer,
                        $saleItem->style, $saleItem->color_item_number,
                    ])) ?: 'Material';
                    return [
                        'name'      => $name,
                        'needed'    => (float) $saleItem->quantity,
                        'allocated' => $allocated,
                        'unit'      => $saleItem->unit ?? '',
                    ];
                })->values();
            }
        }

        [$emailSubject, $emailBody] = $this->resolveEmailTemplate($workOrder, $sale);
        $customerContacts = $sale->opportunity?->parentCustomer?->contacts ?? collect();
        $linkedBill = \App\Models\Bill::where('work_order_id', $workOrder->id)
            ->whereNull('deleted_at')
            ->first();

        return view('pages.work-orders.show', compact('sale', 'workOrder', 'stagingPickTicket', 'materialWarnings', 'emailSubject', 'emailBody', 'customerContacts', 'linkedBill'));
    }

    private function resolveEmailTemplate(WorkOrder $workOrder, Sale $sale): array
    {
        $user            = auth()->user();
        $templateService = app(EmailTemplateService::class);
        $template        = $templateService->getTemplate($user, 'work_order');

        $vars = [
            'customer_name' => $sale->sourceEstimate?->homeowner_name ?: $sale->customer_name,
            'wo_number'     => $workOrder->wo_number,
            'job_name'      => $sale->job_name,
            'job_no'        => $sale->job_no,
            'job_address'   => $sale->job_address,
            'job_phone'     => $sale->job_phone,
            'job_mobile'    => $sale->job_mobile,
            'pm_name'       => $sale->pm_name,
            'pm_first_name' => explode(' ', trim($sale->pm_name ?? ''))[0],
            'sender_name'   => $user->name,
            'sender_email'  => $user->email,
            'wo_link'       => route('mobile.work-orders.show', $workOrder),
        ];

        return [
            $templateService->render($template['subject'], $vars),
            $templateService->render($template['body'], $vars),
        ];
    }

    // ── Stage Work Order — create a staging pick ticket ───────────
    public function stagePickTicket(Request $request, Sale $sale, WorkOrder $workOrder, PickTicketService $service): RedirectResponse
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        // Only one active staging PT per WO
        $alreadyExists = PickTicket::where('work_order_id', $workOrder->id)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($alreadyExists) {
            return back()->with('error', 'This work order already has an active staging pick ticket.');
        }

        $workOrder->load('items.relatedMaterials.saleItem');

        // Collect unique material sale items linked to this WO
        $materialSaleItems = $workOrder->items
            ->flatMap(fn ($item) => $item->relatedMaterials)
            ->map(fn ($mat) => $mat->saleItem)
            ->filter()
            ->unique('id')
            ->values();

        if ($materialSaleItems->isEmpty()) {
            return back()->with('error', 'No materials are linked to this work order. Add material associations before staging.');
        }

        $request->validate([
            'staging_notes'   => ['nullable', 'string', 'max:2000'],
            'fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'delivery_date'   => ['nullable', 'date'],
            'delivery_time'   => ['nullable', 'date_format:H:i'],
        ]);

        $service->createFromWorkOrder(
            $workOrder,
            $request->input('staging_notes'),
            $request->input('fulfillment_type'),
            $request->input('delivery_date'),
            $request->input('delivery_time'),
        );

        return back()->with('success', 'Staging pick ticket created.');
    }

    public function edit(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $workOrder->load([
            'installer',
            'items.relatedMaterials',
            'items.saleItem.room.items' => fn($q) => $q->where('item_type', 'material')
                ->where('is_removed', false)
                ->orderBy('sort_order'),
            'calendarEvent.externalLink',
        ]);
        $installers = Installer::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name', 'email']);
        $maxQtys    = $this->maxQtys($workOrder);

        // Bill lock: if a non-voided bill is recorded, adding new items is blocked
        $billLocked = Bill::where('work_order_id', $workOrder->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['voided'])
            ->exists();

        // Compute available labour items (not yet on this WO, with remaining qty)
        $availableRooms = collect();
        if (! $billLocked) {
            $existingWoSaleItemIds = $workOrder->items->pluck('sale_item_id')->toArray();
            $scheduledByOthers     = $this->scheduledQtys($sale->id, $workOrder->id);

            $saleRooms = $sale->rooms()
                ->with(['items' => fn($q) => $q->whereIn('item_type', ['labour', 'material'])
                    ->where('is_removed', false)
                    ->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get();

            $availableRooms = $saleRooms->map(function ($room) use ($existingWoSaleItemIds, $scheduledByOthers) {
                $labourItems = $room->items
                    ->filter(fn($i) => $i->item_type === 'labour')
                    ->reject(fn($i) => in_array($i->id, $existingWoSaleItemIds))
                    ->map(function ($item) use ($scheduledByOthers) {
                        $effectiveQty        = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                        $scheduled           = (float) ($scheduledByOthers[$item->id] ?? 0);
                        $item->remaining_qty = round($effectiveQty - $scheduled, 2);
                        return $item;
                    })
                    ->filter(fn($i) => $i->remaining_qty > 0)
                    ->values();

                $room->availableLabourItems = $labourItems;
                $room->materialItems        = $room->items->filter(fn($i) => $i->item_type === 'material')->values();
                return $room;
            })->filter(fn($r) => $r->availableLabourItems->isNotEmpty())->values();
        }

        return view('pages.work-orders.edit', compact('sale', 'workOrder', 'installers', 'maxQtys', 'billLocked', 'availableRooms'));
    }

    public function update(Sale $sale, WorkOrder $workOrder, Request $request)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $data = $request->validate([
            'installer_id'         => ['nullable', 'integer', 'exists:installers,id'],
            'scheduled_date'       => ['nullable', 'date'],
            'scheduled_time'       => ['nullable', 'date_format:H:i'],
            'notes'                => ['nullable', 'string'],
            'status'               => ['required', 'string', 'in:' . implode(',', WorkOrder::STATUSES)],
            'calendar_title'       => ['nullable', 'string', 'max:500'],
            'calendar_description' => ['nullable', 'string'],
            'calendar_location'    => ['nullable', 'string', 'max:500'],
            'wo_items'              => ['nullable', 'array'],
            'wo_items.*.quantity'   => ['nullable', 'numeric', 'min:0'],
            'wo_items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'wo_items.*.wo_notes'   => ['nullable', 'string'],
            'wo_items.*.delete'     => ['nullable', 'in:1'],
            'wo_materials'          => ['nullable', 'array'],
            'wo_materials.*'        => ['nullable', 'array'],
            'wo_materials.*.*'      => ['nullable', 'integer', 'exists:sale_items,id'],
            'new_items'             => ['nullable', 'array'],
            'new_qty'               => ['nullable', 'array'],
            'new_qty.*'             => ['nullable', 'numeric', 'min:0.01'],
            'new_cost'              => ['nullable', 'array'],
            'new_cost.*'            => ['nullable', 'numeric', 'min:0'],
            'new_wo_notes'          => ['nullable', 'array'],
            'new_wo_notes.*'        => ['nullable', 'string'],
            'new_materials'         => ['nullable', 'array'],
            'new_materials.*'       => ['nullable', 'array'],
            'new_materials.*.*'     => ['nullable', 'integer', 'exists:sale_items,id'],
        ]);

        // Bill lock check
        $billLocked = Bill::where('work_order_id', $workOrder->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['voided'])
            ->exists();

        // Handle new items — validate before we touch anything
        $newSelectedItems = array_keys($request->input('new_items', []));
        $newSaleItems     = collect();

        if (! empty($newSelectedItems)) {
            if ($billLocked) {
                return back()->withInput()->withErrors([
                    'new_items' => 'This work order has a bill recorded against it. New items cannot be added — create a new Work Order instead.',
                ]);
            }

            $newSaleItems = SaleItem::whereIn('id', $newSelectedItems)->get()->keyBy('id');

            // Validate qty doesn't exceed remaining (schedules for this WO count too)
            $scheduledQtysAll = $this->scheduledQtys($sale->id);
            foreach ($newSelectedItems as $saleItemId) {
                $saleItem = $newSaleItems[$saleItemId] ?? null;
                if (! $saleItem) continue;

                $effectiveQty = $saleItem->order_qty !== null ? (float) $saleItem->order_qty : (float) $saleItem->quantity;
                $qty          = (float) ($request->input("new_qty.{$saleItemId}") ?? $effectiveQty);
                $remaining    = $effectiveQty - (float) ($scheduledQtysAll[$saleItemId] ?? 0);

                if ($qty > $remaining) {
                    return back()->withInput()->withErrors([
                        "new_qty.{$saleItemId}" => "Qty for \"{$saleItem->description}\" exceeds remaining ({$remaining} {$saleItem->unit}).",
                    ]);
                }
            }
        }

        // Enforce: scheduled requires installer + date
        if ($data['status'] === 'scheduled' && $workOrder->status === 'created') {
            if (empty($data['installer_id']) || empty($data['scheduled_date'])) {
                return back()->withInput()->withErrors(['status' => 'Cannot mark as Scheduled without an installer and scheduled date.']);
            }
        }

        // Identify items flagged for deletion
        $deleteItemIds = [];
        foreach ($workOrder->items as $item) {
            if ($request->input("wo_items.{$item->id}.delete") === '1') {
                $deleteItemIds[] = $item->id;
            }
        }

        // Guard: must keep at least one item (accounting for adds and deletes)
        $remainingAfterChanges = $workOrder->items->count() - count($deleteItemIds) + count($newSelectedItems);
        if ($remainingAfterChanges < 1) {
            return back()->withInput()->withErrors(['wo_items' => 'At least one labour item must remain on the work order.']);
        }

        // Validate item qtys (only for items not being deleted)
        $maxQtys = $this->maxQtys($workOrder);
        foreach ($workOrder->items as $item) {
            if (in_array($item->id, $deleteItemIds)) continue;
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

        $previousStatus = $workOrder->status;
        $wasCancelled   = $workOrder->status === 'cancelled';
        $beingCancelled = $data['status'] === 'cancelled';

        DB::transaction(function () use ($workOrder, $data, $request, $deleteItemIds, $newSelectedItems, $newSaleItems) {
            $workOrder->update([
                'installer_id'   => $data['installer_id'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'status'         => $data['status'],
            ]);

            foreach ($workOrder->items as $item) {
                // Delete items flagged for removal (frees up qty for other WOs)
                if (in_array($item->id, $deleteItemIds)) {
                    $item->relatedMaterials()->delete();
                    $item->delete();
                    continue;
                }

                $newQty    = $request->input("wo_items.{$item->id}.quantity");
                $newCost   = $request->input("wo_items.{$item->id}.cost_price");
                $newNotes  = $request->input("wo_items.{$item->id}.wo_notes");

                if ($newQty !== null || $newCost !== null || $newNotes !== null) {
                    $item->update([
                        'quantity'   => $newQty   ?? $item->quantity,
                        'cost_price' => $newCost  ?? $item->cost_price,
                        'wo_notes'   => $newNotes ?? null,
                    ]);
                }

                $materialIds = $request->input("wo_materials.{$item->id}", []);
                $item->relatedMaterials()->delete();
                foreach ($materialIds as $matId) {
                    WorkOrderItemMaterial::create([
                        'work_order_item_id' => $item->id,
                        'sale_item_id'       => (int) $matId,
                    ]);
                }
            }

            // Add new labour items from the sale
            if (! empty($newSelectedItems)) {
                $nextSortOrder = ($workOrder->items->max('sort_order') ?? -1) + 1;
                foreach ($newSelectedItems as $saleItemId) {
                    $saleItem = $newSaleItems[$saleItemId] ?? null;
                    if (! $saleItem) continue;

                    $qty     = (float) ($request->input("new_qty.{$saleItemId}") ?? $saleItem->quantity);
                    $cost    = (float) ($request->input("new_cost.{$saleItemId}") ?? $saleItem->cost_price ?? 0);
                    $woNotes = $request->input("new_wo_notes.{$saleItemId}") ?: null;

                    $woItem = WorkOrderItem::create([
                        'work_order_id' => $workOrder->id,
                        'sale_item_id'  => $saleItem->id,
                        'item_name'     => $this->buildItemName($saleItem),
                        'quantity'      => $qty,
                        'unit'          => $saleItem->unit,
                        'wo_notes'      => $woNotes,
                        'cost_price'    => $cost,
                        'sort_order'    => $nextSortOrder++,
                    ]);

                    foreach ($request->input("new_materials.{$saleItemId}", []) as $matId) {
                        WorkOrderItemMaterial::create([
                            'work_order_item_id' => $woItem->id,
                            'sale_item_id'       => (int) $matId,
                        ]);
                    }
                }
            }
        });

        $workOrder->refresh();

        $syncCalendar = $request->boolean('sync_calendar', false);

        $calendarOverrides = array_filter([
            'title'    => $data['calendar_title'] ?? null,
            'notes'    => $data['calendar_description'] ?? null,
            'location' => $data['calendar_location'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        if (! $wasCancelled) {
            if ($beingCancelled) {
                $this->cancelCalendarEvent($workOrder);
            } elseif (! $syncCalendar && $workOrder->calendar_event_id) {
                $this->cancelCalendarEvent($workOrder);
            } elseif ($syncCalendar && $calendarFieldsChanged) {
                if ($workOrder->calendar_event_id) {
                    $this->syncCalendarUpdate($workOrder, $calendarOverrides);
                } else {
                    $this->syncCalendarCreate($workOrder, $calendarOverrides);
                }
            }
        }

        // Send scheduled SMS when status transitions to scheduled
        if ($data['status'] === 'scheduled' && $previousStatus !== 'scheduled') {
            $this->sendScheduledSms($workOrder);
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

    public function restore(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $workOrder->restore();

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order ' . $workOrder->wo_number . ' restored.');
    }

    public function forceDestroy(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $hasProcessedPickTicket = PickTicket::where('work_order_id', $workOrder->id)
            ->whereNotIn('status', ['staged', 'cancelled'])
            ->exists();

        if ($hasProcessedPickTicket) {
            return back()->with('error', 'Work order ' . $workOrder->wo_number . ' cannot be permanently deleted — it has pick tickets that have been processed.');
        }

        // Cancel any staged pick tickets first
        PickTicket::where('work_order_id', $workOrder->id)
            ->whereIn('status', ['staged'])
            ->update(['status' => 'cancelled']);

        $this->cancelCalendarEvent($workOrder);
        $workOrder->forceDelete();

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order ' . $workOrder->wo_number . ' permanently deleted.');
    }

    // ── PDF ───────────────────────────────────────────────────────

    public function previewPdf(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);
        $workOrder->load(['installer', 'items.relatedMaterials.saleItem', 'items.saleItem.room', 'sale', 'creator']);

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
            'cc'      => ['nullable', 'array'],
            'cc.*'    => ['nullable', 'email'],
        ]);

        $workOrder->load(['installer', 'items.relatedMaterials.saleItem', 'items.saleItem.room', 'sale', 'creator']);

        $user       = auth()->user();
        $mailer     = app(GraphMailService::class);
        $cc         = array_filter($request->input('cc', []));
        $pdfContent = Pdf::loadView('pdf.work-order', compact('workOrder'))->output();

        $attachment = [
            'filename' => $workOrder->wo_number . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        $icsContent = null;
        if ((bool) Setting::get('wo_email_calendar_invite', '0') && $workOrder->scheduled_date) {
            $fromAddress  = Setting::get('mail_from_address', config('services.microsoft.mail_from_address', 'reception@rmflooring.ca'));
            $fromName     = Setting::get('mail_from_name', 'RM Flooring Notifications');
            $installer    = $workOrder->installer;
            $attendees    = [];
            if ($installer && filled($installer->email)) {
                $attendees[] = ['email' => $installer->email, 'name' => $installer->company_name ?: $installer->contact_name ?: $installer->email];
            }
            $timeString = $workOrder->scheduled_time ?? '08:00';
            $start      = Carbon::parse($workOrder->scheduled_date->format('Y-m-d') . ' ' . $timeString);
            $icsContent = app(ICalService::class)->generate(
                uid:            "wo-{$workOrder->id}@rmflooring.ca",
                title:          $request->input('subject'),
                start:          $start,
                end:            $start->copy()->addHours(4),
                organizerEmail: $fromAddress,
                organizerName:  $fromName,
                attendees:      $attendees,
            );
        }

        $pdfUrl  = route('pages.sales.work-orders.pdf', [$sale, $workOrder]);
        $to      = $request->input('to');
        $subject = $request->input('subject');
        $body    = $request->input('body');

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $to, $subject, $body, 'work_order', $attachment, $cc ?: null, $icsContent, $workOrder->id, 'work_order', $pdfUrl)
            : false;

        if (! $sent) {
            $sent = $mailer->send($to, $subject, $body, 'work_order', null, $attachment, $cc ?: null, $icsContent, $workOrder->id, 'work_order', $pdfUrl);
        }

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

    private function buildEventData(WorkOrder $workOrder, array $overrides = []): array
    {
        $sale = $workOrder->relationLoaded('sale') ? $workOrder->sale : Sale::find($workOrder->sale_id);

        $date  = $workOrder->scheduled_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $time  = $workOrder->scheduled_time ?? '08:00';
        $start = Carbon::parse($date . ' ' . $time);
        $end   = $start->copy()->addHours(2);

        $homeownerName = $sale->homeowner_name ?? $sale->customer_name ?? $sale->job_name ?? 'Customer';
        $itemSummary   = $workOrder->items->map(fn($i) => $i->item_name . ' (' . $i->quantity . ' ' . $i->unit . ')')->implode(', ');

        $vars = [
            'wo_number'            => $workOrder->wo_number ?? '',
            'installer_name'       => $workOrder->installer?->company_name ?? '',
            'installer_first_name' => explode(' ', trim($workOrder->installer?->company_name ?? 'Installer'))[0],
            'customer_name'        => $homeownerName,
            'sale_number'          => $sale->sale_number ?? '',
            'job_address'          => $sale->job_address ?? '',
            'items_summary'        => $itemSummary,
            'wo_notes'             => $workOrder->notes ?? '',
            'pm_name'              => $sale->pm_name ?? '',
            'pm_first_name'        => explode(' ', trim($sale->pm_name ?? ''))[0],
        ];

        $rendered = app(CalendarTemplateService::class)->renderTemplate('work_order_calendar', $vars);

        return [
            'title'    => $overrides['title'] ?? $rendered['title'],
            'start'    => $start,
            'end'      => $end,
            'location' => $overrides['location'] ?? $sale->job_address ?? null,
            'notes'    => $overrides['notes'] ?? $rendered['notes'],
        ];
    }

    /**
     * Create a calendar event on RM – Installations group calendar (best-effort).
     * Uses the currently logged-in user's MS account to write to the group calendar.
     */
    private function syncCalendarCreate(WorkOrder $workOrder, array $overrides = []): void
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

            $eventData  = $this->buildEventData($workOrder, $overrides);
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
            session()->flash('warning', 'Work order saved, but the calendar event could not be created. Your Microsoft 365 connection may have expired — check Settings → Integrations to reconnect.');
        }
    }

    private function syncCalendarUpdate(WorkOrder $workOrder, array $overrides = []): void
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

            $eventData = $this->buildEventData($workOrder, $overrides);
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

    // ── SMS helpers ───────────────────────────────────────────────

    /**
     * Send WO scheduled SMS to configured recipients (best-effort, never blocks).
     */
    private function sendScheduledSms(WorkOrder $workOrder): void
    {
        if (! \App\Models\Setting::get('sms_notify_wo_scheduled')) {
            return;
        }

        try {
            $workOrder->loadMissing(['installer', 'sale.opportunity.projectManager', 'sale.opportunity.parentCustomer', 'sale.sourceEstimate']);

            $sale        = $workOrder->sale;
            $installer   = $workOrder->installer;
            $pm          = $sale?->opportunity?->projectManager;

            $scheduledDate = $workOrder->scheduled_date?->format('M j, Y') ?? '';
            $scheduledTime = $workOrder->scheduled_time
                ? \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:ia')
                : '';

            $vars = [
                'wo_number'            => $workOrder->wo_number ?? '',
                'sale_number'          => $sale?->sale_number ?? '',
                'customer_name'        => $sale?->homeowner_name ?? $sale?->job_name ?? '',
                'job_address'          => $sale?->job_address ?? '',
                'job_phone'            => $sale?->job_phone ?? '',
                'job_mobile'           => $sale?->job_mobile ?? '',
                'scheduled_date'       => $scheduledDate,
                'scheduled_time'       => $scheduledTime,
                'installer_name'       => $installer?->company_name ?? '',
                'installer_first_name' => explode(' ', trim($installer?->company_name ?? 'Installer'))[0],
                'pm_name'              => $pm?->name ?? '',
                'pm_first_name'        => explode(' ', trim($pm?->name ?? ''))[0],
            ];

            $recipients   = array_filter(explode(',', \App\Models\Setting::get('sms_wo_scheduled_to', 'pm,installer')));
            $sms          = new SmsService();
            $tpl          = new SmsTemplateService();
            $body         = $tpl->renderTemplate('wo_scheduled', $vars);
            $bodyCustomer = $tpl->renderTemplate('wo_scheduled_customer', $vars);

            if (in_array('pm', $recipients) && $pm?->mobile) {
                $sms->send($pm->mobile, $body, 'wo_scheduled', $workOrder);
            }

            if (in_array('installer', $recipients) && $installer?->mobile) {
                $sms->send($installer->mobile, $body, 'wo_scheduled', $workOrder);
            }

            if (in_array('homeowner', $recipients) && !$sale?->opportunity?->parentCustomer?->sms_opted_out) {
                $phone = $sale?->job_phone ?? $sale?->sourceEstimate?->homeowner_phone ?? null;
                if ($phone) {
                    $sms->send($phone, $bodyCustomer, 'wo_scheduled_customer', $workOrder);
                }
            }
        } catch (\Throwable $e) {
            Log::error('[WO SMS] scheduled send failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
