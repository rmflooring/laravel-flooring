<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Installer;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\BillableQuantityService;
use App\Services\CalendarTemplateService;
use App\Services\GraphCalendarService;
use App\Services\GraphMailService;
use App\Services\InventoryService;
use App\Services\PickTicketService;
use App\Models\WorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderController extends Controller
{
    const WAREHOUSE_GROUP_ID = '4bfd495c-4df2-4eaa-9d8c-987c4ef23b02';

    // -------------------------------------------------------------------------
    // Index — all purchase orders with search/filters
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $q        = trim($request->input('q', ''));
        $status   = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');

        $purchaseOrders = PurchaseOrder::with(['vendor', 'sale', 'items'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('po_number', 'like', "%{$q}%")
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('company_name', 'like', "%{$q}%"))
                        ->orWhereHas('sale', fn ($sq) => $sq->where('sale_number', 'like', "%{$q}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $statusOptions = ['pending', 'ordered', 'received', 'cancelled'];

        return view('pages.purchase-orders.index', compact(
            'purchaseOrders', 'statusOptions', 'q', 'status', 'dateFrom', 'dateTo'
        ));
    }

    // -------------------------------------------------------------------------
    // Catalog search API — for stock PO item rows
    // -------------------------------------------------------------------------

    public function catalogSearch(Request $request)
    {
        $q        = trim($request->input('q', ''));
        $vendorId = (int) $request->input('vendor_id', 0);

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = \App\Models\ProductStyle::query()
            ->where('product_styles.status', 'active')
            ->join('product_lines', 'product_lines.id', '=', 'product_styles.product_line_id')
            ->where('product_lines.status', 'active')
            ->join('product_types', 'product_types.id', '=', 'product_lines.product_type_id')
            ->leftJoin('unit_measures', 'unit_measures.id', '=', 'product_lines.unit_id')
            ->select([
                'product_styles.id',
                'product_styles.name as style_name',
                'product_styles.color',
                'product_styles.cost_price',
                'product_styles.use_box_qty',
                'product_styles.units_per',
                'product_lines.id as product_line_id',
                'product_lines.name as line_name',
                'product_lines.manufacturer',
                'product_lines.vendor_id',
                'product_types.name as product_type',
                'unit_measures.label as unit_label',
            ])
            ->where(function ($sub) use ($q) {
                $sub->where('product_types.name', 'like', "%{$q}%")
                    ->orWhere('product_lines.manufacturer', 'like', "%{$q}%")
                    ->orWhere('product_lines.name', 'like', "%{$q}%")
                    ->orWhere('product_styles.name', 'like', "%{$q}%")
                    ->orWhere('product_styles.color', 'like', "%{$q}%")
                    ->orWhere('product_styles.sku', 'like', "%{$q}%");
            });

        if ($vendorId > 0) {
            $query->where('product_lines.vendor_id', $vendorId);
        }

        $results = $query->orderBy('product_types.name')
            ->orderBy('product_lines.manufacturer')
            ->orderBy('product_lines.name')
            ->orderBy('product_styles.name')
            ->limit(25)
            ->get()
            ->map(function ($row) {
                $parts = array_filter([
                    $row->product_type,
                    $row->manufacturer,
                    $row->line_name,
                    $row->style_name,
                    $row->color,
                ]);
                $label = implode(' — ', $parts);

                return [
                    'id'               => $row->id,
                    'product_style_id' => $row->id,
                    'label'            => $label,
                    'item_name'        => $label,
                    'cost_price'       => (float) $row->cost_price,
                    'unit'             => $row->unit_label ?? '',
                    'manufacturer'     => $row->manufacturer,
                    'product_type'     => $row->product_type,
                    'vendor_id'        => $row->vendor_id,
                    'use_box_qty'      => (bool) $row->use_box_qty,
                    'units_per'        => (float) ($row->units_per ?? 0),
                ];
            });

        return response()->json($results);
    }

    // -------------------------------------------------------------------------
    // Create form — stock PO (no sale)
    // -------------------------------------------------------------------------

    public function createStock()
    {
        $installerVendorIds = Installer::whereNotNull('vendor_id')->pluck('vendor_id');

        $vendors = Vendor::where('status', 'active')
            ->whereNotIn('id', $installerVendorIds)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'email', 'address', 'address2', 'city', 'province', 'postal_code']);

        $warehouseAddress = $this->warehouseAddress();

        return view('pages.purchase-orders.create-stock', compact('vendors', 'warehouseAddress'));
    }

    // -------------------------------------------------------------------------
    // Store — stock PO (no sale)
    // -------------------------------------------------------------------------

    public function storeStock(Request $request)
    {
        $data = $request->validate([
            'vendor_id'               => ['required', 'integer', 'exists:vendors,id'],
            'expected_delivery_date'  => ['nullable', 'date'],
            'fulfillment_method'      => ['required', 'in:delivery_warehouse,delivery_custom,pickup'],
            'delivery_address'        => ['nullable', 'string', 'max:500'],
            'special_instructions'    => ['nullable', 'string'],
            'pickup_date'             => ['nullable', 'date'],
            'pickup_time'             => ['nullable', 'date_format:H:i'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_style_id' => ['nullable', 'integer', 'exists:product_styles,id'],
            'items.*.item_name'        => ['required', 'string', 'max:255'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'             => ['nullable', 'string', 'max:50'],
            'items.*.cost_price'       => ['required', 'numeric', 'min:0'],
            'items.*.po_notes'         => ['nullable', 'string'],
        ]);

        $resolvedAddress = $this->resolveDeliveryAddress(
            $data['fulfillment_method'],
            $data['delivery_address'] ?? null,
            null,
        );

        $po = DB::transaction(function () use ($data, $resolvedAddress) {
            $pickupAt = null;
            if ($data['fulfillment_method'] === 'pickup' && ! empty($data['pickup_date']) && ! empty($data['pickup_time'])) {
                $pickupAt = \Carbon\Carbon::parse($data['pickup_date'] . ' ' . $data['pickup_time']);
            }

            $po = PurchaseOrder::create([
                'sale_id'                => null,
                'opportunity_id'         => null,
                'vendor_id'              => $data['vendor_id'],
                'status'                 => 'pending',
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'fulfillment_method'     => $data['fulfillment_method'],
                'delivery_address'       => $resolvedAddress,
                'special_instructions'   => $data['special_instructions'] ?? null,
                'pickup_at'              => $pickupAt,
            ]);

            foreach ($data['items'] as $i => $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sale_item_id'      => null,
                    'product_style_id'  => ! empty($item['product_style_id']) ? (int) $item['product_style_id'] : null,
                    'item_name'         => $item['item_name'],
                    'quantity'          => (float) $item['quantity'],
                    'unit'              => $item['unit'] ?? null,
                    'cost_price'        => (float) $item['cost_price'],
                    'po_notes'          => ! empty($item['po_notes']) ? $item['po_notes'] : null,
                    'sort_order'        => $i,
                ]);
            }

            return $po;
        });

        if ($po->fulfillment_method === 'pickup' && $po->pickup_at) {
            $this->syncCalendarCreate($po);
        }

        return redirect()
            ->route('pages.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    // -------------------------------------------------------------------------
    // Create form — scoped to a sale
    // -------------------------------------------------------------------------

    public function create(Sale $sale)
    {
        if ($sale->status === 'change_in_progress') {
            return redirect()
                ->route('pages.sales.show', $sale)
                ->with('error', 'This sale has an active Change Order. Purchase Orders cannot be created until the Change Order is approved or cancelled.');
        }

        if ($sale->status !== 'approved') {
            return redirect()
                ->route('pages.sales.show', $sale)
                ->with('error', 'The sale must be approved before a purchase order can be created.');
        }

        $sale->load([
            'rooms' => fn ($q) => $q->orderBy('sort_order'),
            'rooms.items' => fn ($q) => $q->where('item_type', 'material')
                                          ->where('is_removed', false)
                                          ->orderBy('sort_order'),
            'rooms.items.productLine',
            'rooms.items.productStyle',
        ]);

        $installerVendorIds = Installer::whereNotNull('vendor_id')->pluck('vendor_id');

        $vendors = Vendor::where('status', 'active')
            ->whereNotIn('id', $installerVendorIds)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'email', 'address', 'address2', 'city', 'province', 'postal_code']);

        $warehouseAddress = $this->warehouseAddress();

        // Remaining qty available per sale item (across all non-cancelled POs)
        $orderedQtys   = $this->orderedQtys($sale->id);
        $remainingQtys = [];
        $itemVendorMap = []; // sale_item_id => vendor_id (resolved via product_line or manufacturer name match)
        $materialKeys  = []; // sale_item_id => "same material" key — lines sharing one can be combined for the vendor
        $itemInfo      = []; // sale_item_id => room / name / unit, for the combine prompt
        foreach ($sale->rooms as $room) {
            foreach ($room->items as $item) {
                $materialKeys[$item->id] = $this->materialKey($item);
                $itemInfo[$item->id]     = ['room' => $room->room_name ?: 'Unnamed Room', 'name' => $this->buildItemName($item), 'unit' => $item->unit];
                $effectiveQty = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                $remainingQtys[$item->id] = max(0, $effectiveQty - ($orderedQtys[$item->id] ?? 0));

                // 1. Hard link via product_style.vendor_id (most specific)
                $vendorId = $item->productStyle?->vendor_id;

                // 2. Fallback: product_line.vendor_id
                if (! $vendorId) $vendorId = $item->productLine?->vendor_id;

                // 3. Fallback: look up product line by manufacturer + style name text
                //    Handles items where product_line_id wasn't saved (typed manually or pre-migration)
                if (! $vendorId && $item->manufacturer && $item->style) {
                    $productLine = \App\Models\ProductLine::where('manufacturer', trim($item->manufacturer))
                        ->where('name', trim($item->style))
                        ->whereNotNull('vendor_id')
                        ->first();
                    $vendorId = $productLine?->vendor_id;
                }

                // 4. Fallback: match item manufacturer name against vendor company_name
                //    Try exact match first, then "vendor name contains manufacturer" (e.g. "Shaw" in "Shaw Floors")
                if (! $vendorId && $item->manufacturer) {
                    $mfr   = trim($item->manufacturer);
                    $match = $vendors->first(fn ($v) => strcasecmp(trim($v->company_name), $mfr) === 0)
                          ?? $vendors->first(fn ($v) => stripos(trim($v->company_name), $mfr) !== false);
                    $vendorId = $match?->id;
                }

                $itemVendorMap[$item->id] = $vendorId;
            }
        }

        return view('pages.purchase-orders.create', compact('sale', 'vendors', 'warehouseAddress', 'remainingQtys', 'itemVendorMap', 'materialKeys', 'itemInfo'));
    }

    // -------------------------------------------------------------------------
    // Combine preview — same material across rooms → one vendor line
    // -------------------------------------------------------------------------

    /**
     * Called by the create form before submitting, for the groups the user chose
     * to combine: checks each group really is the same material and works out
     * any full-box rounding of the combined total (the user confirms it).
     */
    public function combinePreview(Request $request, Sale $sale)
    {
        $request->validate([
            'items'     => ['required', 'array'],
            'items.*'   => ['integer'],
            'qty'       => ['nullable', 'array'],
            'cost'      => ['nullable', 'array'],
            'combine'   => ['required', 'array'],
            'combine.*' => ['string', 'max:40'],
        ]);

        $groups = $this->resolveCombineGroups($sale, $request->input('items', []), $request->input('combine', []),
            $request->input('qty', []), $request->input('cost', []));

        return response()->json(['groups' => array_values(array_map(fn ($g) => [
            'token'    => $g['token'],
            'ok'       => $g['error'] === null,
            'error'    => $g['error'],
            'name'     => $g['name'],
            'unit'     => $g['unit'],
            'combined' => $g['combined'],
            'rows'     => $g['rows'],
            'box'      => $g['box'],
        ], $groups))]);
    }

    /**
     * Key identifying "exactly the same material": the catalog style + unit, or
     * for typed-in lines the full description + unit.
     */
    private function materialKey(\App\Models\SaleItem $item): string
    {
        $unit = strtolower(trim((string) $item->unit));

        return $item->product_style_id
            ? 'style:' . $item->product_style_id . '|' . $unit
            : 'text:' . strtolower(trim($this->buildItemName($item))) . '|' . $unit;
    }

    /**
     * Turn the create form's combine[sale_item_id] = token input into validated
     * groups (2+ selected lines, same material, same unit cost), each with its
     * per-room rows and — when the style is sold by the box — a box-rounding plan.
     *
     * @return array<string, array{token: string, item_ids: int[], error: ?string, name: string, unit: ?string, combined: float, rows: array, box: ?array}>
     */
    private function resolveCombineGroups(Sale $sale, array $selectedIds, array $combine, array $qtyInput, array $costInput): array
    {
        $selectedIds = array_map('intval', $selectedIds);
        $byToken = [];
        foreach ($combine as $itemId => $token) {
            if ($token !== '' && in_array((int) $itemId, $selectedIds, true)) {
                $byToken[(string) $token][] = (int) $itemId;
            }
        }
        $byToken = array_filter($byToken, fn ($ids) => count($ids) > 1);
        if (! $byToken) {
            return [];
        }

        $saleItems = $sale->items()
            ->with(['productStyle', 'room'])
            ->where('item_type', 'material')
            ->where('is_removed', false)
            ->whereIn('id', array_merge(...array_values($byToken)))
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');
        $orderedQtys = $this->orderedQtys($sale->id);

        $groups = [];
        foreach ($byToken as $token => $ids) {
            $items = collect($ids)->map(fn ($id) => $saleItems->get($id))->filter()->values();
            $first = $items->first();
            $group = [
                'token'    => $token,
                'item_ids' => $items->pluck('id')->all(),
                'error'    => null,
                'name'     => $first ? $this->buildItemName($first) : '',
                'unit'     => $first?->unit,
                'combined' => 0.0,
                'rows'     => [],
                'box'      => null,
            ];

            $submitted = [];
            $costs     = [];
            foreach ($items as $item) {
                $effective = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                $remaining = max(0, $effective - ($orderedQtys[$item->id] ?? 0));
                $submitted[$item->id] = isset($qtyInput[$item->id]) && $qtyInput[$item->id] !== '' ? (float) $qtyInput[$item->id] : $remaining;
                $costs[$item->id]     = isset($costInput[$item->id]) && $costInput[$item->id] !== '' ? round((float) $costInput[$item->id], 3) : round((float) $item->cost_price, 3);
                $group['rows'][] = ['sale_item_id' => $item->id, 'room' => $item->room?->room_name ?: 'Unnamed Room', 'qty' => $submitted[$item->id]];
            }
            $group['combined'] = round(array_sum($submitted), 2);

            if ($items->count() < 2) {
                $group['error'] = 'Not enough lines to combine.';
            } elseif ($items->map(fn ($i) => $this->materialKey($i))->unique()->count() > 1) {
                $group['error'] = 'These lines are not the exact same material.';
            } elseif (count(array_unique(array_map('strval', $costs))) > 1) {
                $group['error'] = 'The unit costs are different, so they can\'t be shown as one line.';
            } else {
                $group['box'] = $this->planBoxRounding($items, $submitted, $orderedQtys);
            }

            $groups[$token] = $group;
        }

        return $groups;
    }

    /**
     * Full-box check on a combined line. When every room is being ordered in full
     * (nothing on other POs yet), the boxes are worked out from the rooms' actual
     * Qty — combining often needs fewer boxes than rounding each room separately.
     * Otherwise the combined total is just rounded up to the next full box.
     *
     * Returns null when the combined total already is the right number of boxes;
     * else the suggested total, its per-room split (extra goes on the first room),
     * and the sale lines whose Order Qty must change so they read fully ordered.
     */
    private function planBoxRounding($items, array $submitted, array $orderedQtys): ?array
    {
        $style    = $items->first()->productStyle;
        $unitsPer = (float) ($style?->units_per ?? 0);
        if (! $style?->use_box_qty || $unitsPer <= 0) {
            return null;
        }

        $full = $items->every(function ($item) use ($submitted, $orderedQtys) {
            $effective = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
            return ($orderedQtys[$item->id] ?? 0) <= 0 && abs($submitted[$item->id] - $effective) < 0.001;
        });

        $base = [];
        foreach ($items as $item) {
            $base[$item->id] = $full ? (float) $item->quantity : $submitted[$item->id];
        }

        $combined  = array_sum($submitted);
        $boxes     = (int) ceil(round(array_sum($base) / $unitsPer, 6));
        $suggested = round($boxes * $unitsPer, 2);
        if ($boxes <= 0 || abs($suggested - $combined) < 0.001) {
            return null;
        }

        $lines = $base;
        $firstId = $items->first()->id;
        $lines[$firstId] = round($lines[$firstId] + ($suggested - array_sum($base)), 2);

        $orderQty = [];
        foreach ($items as $item) {
            $effective = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
            $needed    = round(($orderedQtys[$item->id] ?? 0) + $lines[$item->id], 2);
            if ($full ? abs($needed - $effective) >= 0.001 : $needed > $effective + 0.001) {
                $orderQty[$item->id] = ['from' => $effective, 'to' => $needed, 'room' => $item->room?->room_name ?: 'Unnamed Room'];
            }
        }

        return [
            'units_per'       => $unitsPer,
            'current_boxes'   => round($combined / $unitsPer, 2),
            'suggested'       => $suggested,
            'suggested_boxes' => $boxes,
            'need'            => round(array_sum($base), 2),
            'lines'           => array_map(fn ($q) => round($q, 2), $lines),
            'order_qty'       => $orderQty,
        ];
    }

    // -------------------------------------------------------------------------
    // Store — create a new PO for a sale
    // -------------------------------------------------------------------------

    public function store(Request $request, Sale $sale)
    {
        if ($sale->status !== 'approved') {
            return redirect()
                ->route('pages.sales.show', $sale)
                ->with('error', 'The sale must be approved before a purchase order can be created.');
        }

        $data = $request->validate([
            'vendor_id'               => ['required', 'integer', 'exists:vendors,id'],
            'expected_delivery_date'  => ['nullable', 'date'],
            'fulfillment_method'      => ['required', 'in:delivery_site,delivery_warehouse,delivery_custom,pickup'],
            'delivery_address'        => ['nullable', 'string', 'max:500'],
            'special_instructions'    => ['nullable', 'string'],
            'pickup_date'             => ['nullable', 'date'],
            'pickup_time'             => ['nullable', 'date_format:H:i'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*'                 => ['integer', 'exists:sale_items,id'],
            'qty'                     => ['nullable', 'array'],
            'qty.*'                   => ['nullable', 'numeric', 'min:0'],
            'cost'                    => ['nullable', 'array'],
            'cost.*'                  => ['nullable', 'numeric', 'min:0'],
            'po_notes'                => ['nullable', 'array'],
            'po_notes.*'              => ['nullable', 'string'],
            'combine'                 => ['nullable', 'array'],
            'combine.*'               => ['nullable', 'string', 'max:40'],
            'box_round'               => ['nullable', 'array'],
        ]);

        // Same material in several rooms, combined into one vendor line
        $combineGroups = $this->resolveCombineGroups($sale, $data['items'], $data['combine'] ?? [], $data['qty'] ?? [], $data['cost'] ?? []);
        $combineErrors = [];
        $orderQtyUpdates = []; // sale_item_id => new order_qty (accepted box rounding)
        foreach ($combineGroups as $token => $group) {
            if ($group['error']) {
                $combineErrors['combine'] = '"' . $group['name'] . '" — ' . $group['error'];
                continue;
            }
            if ($group['box'] && ! empty($data['box_round'][$token])) {
                foreach ($group['box']['lines'] as $itemId => $qty) {
                    $data['qty'][$itemId] = $qty;
                }
                foreach ($group['box']['order_qty'] as $itemId => $change) {
                    $orderQtyUpdates[$itemId] = $change['to'];
                }
            }
        }
        if ($combineErrors) {
            return back()->withErrors($combineErrors)->withInput();
        }

        // Validate qty overrides don't exceed remaining available per item
        $orderedQtys   = $this->orderedQtys($sale->id);
        $qtyOverrides  = $data['qty'] ?? [];
        $saleItemsForValidation = $sale->items()
            ->where('item_type', 'material')
            ->where('is_removed', false)
            ->whereIn('id', $data['items'])
            ->get()
            ->keyBy('id');

        $qtyErrors = [];
        foreach ($data['items'] as $itemId) {
            if (! isset($saleItemsForValidation[$itemId])) {
                continue;
            }
            $saleItem  = $saleItemsForValidation[$itemId];
            $effectiveQty = $orderQtyUpdates[$itemId]
                ?? ($saleItem->order_qty !== null ? (float) $saleItem->order_qty : (float) $saleItem->quantity);
            $remaining = max(0, $effectiveQty - ($orderedQtys[$itemId] ?? 0));
            $submitted = isset($qtyOverrides[$itemId]) && $qtyOverrides[$itemId] !== ''
                ? (float) $qtyOverrides[$itemId]
                : $effectiveQty;

            if ($submitted > $remaining + 0.001) {
                $qtyErrors["qty.{$itemId}"] = '"' . $this->buildItemName($saleItem) . '" — qty ' . $submitted . ' exceeds remaining available qty of ' . $remaining . '.';
            }
        }

        if (! empty($qtyErrors)) {
            return back()->withErrors($qtyErrors)->withInput();
        }

        $resolvedAddress = $this->resolveDeliveryAddress(
            $data['fulfillment_method'],
            $data['delivery_address'] ?? null,
            $sale,
        );

        // Each combined group gets one id shared by its PO lines
        $vendorGroupFor = [];
        foreach ($combineGroups as $group) {
            $groupId = (string) \Illuminate\Support\Str::uuid();
            foreach ($group['item_ids'] as $itemId) {
                $vendorGroupFor[$itemId] = $groupId;
            }
        }

        $po = DB::transaction(function () use ($data, $sale, $resolvedAddress, $vendorGroupFor, $orderQtyUpdates) {

            // Accepted full-box rounding: keep the sale's Order Qty in step with what's ordered
            foreach ($orderQtyUpdates as $itemId => $orderQty) {
                \App\Models\SaleItem::where('id', $itemId)->where('sale_id', $sale->id)->update(['order_qty' => $orderQty]);
            }

            $pickupAt = null;
            if ($data['fulfillment_method'] === 'pickup' && ! empty($data['pickup_date']) && ! empty($data['pickup_time'])) {
                $pickupAt = \Carbon\Carbon::parse($data['pickup_date'] . ' ' . $data['pickup_time']);
            }

            $po = PurchaseOrder::create([
                'sale_id'                => $sale->id,
                'opportunity_id'         => $sale->opportunity_id,
                'vendor_id'              => $data['vendor_id'],
                'status'                 => 'pending',
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'fulfillment_method'     => $data['fulfillment_method'],
                'delivery_address'       => $resolvedAddress,
                'special_instructions'   => $data['special_instructions'] ?? null,
                'pickup_at'              => $pickupAt,
            ]);

            $saleItems = $sale->items()
                ->where('item_type', 'material')
                ->where('is_removed', false)
                ->whereIn('id', $data['items'])
                ->orderBy('sort_order')
                ->get();

            $qtyOverrides   = $data['qty']      ?? [];
            $costOverrides  = $data['cost']     ?? [];
            $notesOverrides = $data['po_notes'] ?? [];

            foreach ($saleItems as $i => $item) {
                $qty   = isset($qtyOverrides[$item->id])   && $qtyOverrides[$item->id]   !== '' ? (float) $qtyOverrides[$item->id]  : (float) $item->quantity;
                $cost  = isset($costOverrides[$item->id])  && $costOverrides[$item->id]  !== '' ? (float) $costOverrides[$item->id] : (float) $item->cost_price;
                $notes = isset($notesOverrides[$item->id]) && $notesOverrides[$item->id] !== '' ? $notesOverrides[$item->id] : ($item->po_notes ?: null);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sale_item_id'      => $item->id,
                    'vendor_group'      => $vendorGroupFor[$item->id] ?? null,
                    'item_name'         => $this->buildItemName($item),
                    'quantity'          => $qty,
                    'unit'              => $item->unit,
                    'po_notes'          => $notes,
                    'cost_price'        => $cost,
                    'sort_order'        => $i,
                ]);
            }

            return $po;
        });

        if ($po->fulfillment_method === 'pickup' && $po->pickup_at) {
            $this->syncCalendarCreate($po);
        }

        return redirect()
            ->route('pages.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    // -------------------------------------------------------------------------
    // Show — read-only
    // -------------------------------------------------------------------------

    public function show(PurchaseOrder $purchaseOrder, BillableQuantityService $billableQuantityService)
    {
        $purchaseOrder->load(['vendor', 'items.saleItem.room', 'sale', 'orderedBy', 'documents.uploader']);

        // Scope: within same sale (if sale-linked), or within all stock POs
        $base = PurchaseOrder::query();
        if ($purchaseOrder->sale_id) {
            $base->where('sale_id', $purchaseOrder->sale_id);
        } else {
            $base->whereNull('sale_id');
        }

        // Prev = newer in desc order (higher in the index list)
        $prev = (clone $base)
            ->where(function ($q) use ($purchaseOrder) {
                $q->where('created_at', '>', $purchaseOrder->created_at)
                  ->orWhere(function ($q2) use ($purchaseOrder) {
                      $q2->where('created_at', $purchaseOrder->created_at)
                         ->where('id', '>', $purchaseOrder->id);
                  });
            })
            ->orderBy('created_at')->orderBy('id')
            ->first();

        // Next = older in desc order (lower in the index list)
        $next = (clone $base)
            ->where(function ($q) use ($purchaseOrder) {
                $q->where('created_at', '<', $purchaseOrder->created_at)
                  ->orWhere(function ($q2) use ($purchaseOrder) {
                      $q2->where('created_at', $purchaseOrder->created_at)
                         ->where('id', '<', $purchaseOrder->id);
                  });
            })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first();

        // Position (1 = newest): count how many are newer than or equal (by id at same time)
        $position = (clone $base)
            ->where(function ($q) use ($purchaseOrder) {
                $q->where('created_at', '>', $purchaseOrder->created_at)
                  ->orWhere(function ($q2) use ($purchaseOrder) {
                      $q2->where('created_at', $purchaseOrder->created_at)
                         ->where('id', '>=', $purchaseOrder->id);
                  });
            })
            ->count();

        $total = (clone $base)->count();

        $bills = $purchaseOrder->bills()->with('vendor')->get();
        $billableQty = $billableQuantityService->forPurchaseOrder($purchaseOrder);
        $hasRemainingToBill = $billableQuantityService->hasRemainingBillableQty($purchaseOrder);

        return view('pages.purchase-orders.show', compact(
            'purchaseOrder', 'prev', 'next', 'position', 'total', 'bills', 'billableQty', 'hasRemainingToBill'
        ));
    }

    // -------------------------------------------------------------------------
    // Jump — redirect to a PO by number
    // -------------------------------------------------------------------------

    public function jump(Request $request)
    {
        $q  = trim($request->input('q', ''));
        $po = PurchaseOrder::where('po_number', $q)->first();

        if ($po) {
            return redirect()->route('pages.purchase-orders.show', $po);
        }

        return redirect()->back()->with('error', "No purchase order found with number \"{$q}\".");
    }

    // -------------------------------------------------------------------------
    // Receive items form
    // -------------------------------------------------------------------------

    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->status === 'ordered', 403, 'Only ordered POs can be received.');

        $purchaseOrder->load(['vendor', 'items.saleItem.room', 'sale']);

        return view('pages.purchase-orders.receive', compact('purchaseOrder'));
    }

    // -------------------------------------------------------------------------
    // Process receipt — creates InventoryReceipts and marks PO received
    // -------------------------------------------------------------------------

    public function receive(Request $request, PurchaseOrder $purchaseOrder, InventoryService $inventory, PickTicketService $pickTickets)
    {
        abort_unless($purchaseOrder->status === 'ordered', 403, 'Only ordered POs can be received.');

        $purchaseOrder->load(['items.saleItem']);

        $request->validate([
            'received_date'   => ['required', 'date', 'before_or_equal:today'],
            'quantities'      => ['required', 'array'],
            'quantities.*'    => ['required', 'numeric', 'min:0'],
        ]);

        $receivedDate = $request->input('received_date');
        $quantities   = $request->input('quantities', []);

        // At least one item must have qty > 0
        $anyReceived = collect($quantities)->contains(fn ($qty) => (float) $qty > 0);
        if (! $anyReceived) {
            return back()
                ->withErrors(['quantities' => 'Enter a received quantity for at least one item.'])
                ->withInput();
        }

        DB::transaction(function () use ($purchaseOrder, $inventory, $pickTickets, $receivedDate, $quantities) {
            foreach ($purchaseOrder->items as $poItem) {
                $qty = (float) ($quantities[$poItem->id] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $receipt = $inventory->receiveFromPOItem($poItem, $qty, $receivedDate);

                // Auto-allocate and create a pick ticket for sale-linked items
                if ($poItem->sale_item_id && $poItem->saleItem) {
                    $allocation = $inventory->allocate($receipt, $poItem->saleItem, $qty);

                    $workOrder = WorkOrder::where('sale_id', $purchaseOrder->sale_id)
                        ->where('status', '<>', 'cancelled')
                        ->whereHas('items.relatedMaterials', fn ($q) => $q->where('sale_item_id', $poItem->sale_item_id))
                        ->first();

                    $pickTickets->createFromAllocation($allocation, $workOrder);
                }
            }

            $purchaseOrder->update(['status' => 'received']);
        });

        return redirect()
            ->route('pages.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Items received and added to inventory.');
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.saleItem.room', 'sale']);

        $installerVendorIds = Installer::whereNotNull('vendor_id')->pluck('vendor_id');

        $vendors = Vendor::where('status', 'active')
            ->whereNotIn('id', $installerVendorIds)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'email', 'address', 'address2', 'city', 'province', 'postal_code']);

        $warehouseAddress = $this->warehouseAddress();

        // Stock PO (no sale) — free-form items, no qty constraints
        if (! $purchaseOrder->sale_id) {
            return view('pages.purchase-orders.edit-stock', compact('purchaseOrder', 'vendors', 'warehouseAddress'));
        }

        // Max qty each PO item can be set to (sale item qty minus what other non-cancelled POs have)
        $orderedByOthers = $this->orderedQtys($purchaseOrder->sale_id, $purchaseOrder->id);
        $maxQtys = $purchaseOrder->items->mapWithKeys(function ($poItem) use ($orderedByOthers) {
            $saleItem = $poItem->saleItem;
            if ($saleItem) {
                $saleQty = $saleItem->order_qty !== null ? (float) $saleItem->order_qty : (float) $saleItem->quantity;
                $max     = max(0, $saleQty - ($orderedByOthers[$poItem->sale_item_id] ?? 0));
            } else {
                // No linked sale item — fall back to the PO item's own quantity so the input isn't blocked
                $saleQty = (float) $poItem->quantity;
                $max     = $saleQty;
            }
            return [$poItem->id => ['max' => $max, 'sale_qty' => $saleQty]];
        })->toArray();

        // For pending/ordered POs: find sale items that can still be added
        $addableRooms = collect();
        $addableRemainingQtys = [];

        if (in_array($purchaseOrder->status, ['pending', 'ordered'])) {
            $purchaseOrder->sale->load([
                'rooms'             => fn ($q) => $q->orderBy('sort_order'),
                'rooms.items'       => fn ($q) => $q->where('item_type', 'material')
                                                    ->where('is_removed', false)
                                                    ->orderBy('sort_order'),
                'rooms.items.productLine',
                'rooms.items.productStyle',
            ]);

            $existingPoSaleItemIds = $purchaseOrder->items->pluck('sale_item_id')->filter()->flip()->toArray();
            $poVendorId            = $purchaseOrder->vendor_id;

            foreach ($purchaseOrder->sale->rooms as $room) {
                $roomItems = [];
                foreach ($room->items as $item) {
                    if (isset($existingPoSaleItemIds[$item->id])) {
                        continue;
                    }

                    $effectiveQty = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                    $remaining    = max(0, $effectiveQty - ($orderedByOthers[$item->id] ?? 0));
                    if ($remaining <= 0) {
                        continue;
                    }

                    // Resolve vendor (mirrors create() logic)
                    $vendorId = $item->productStyle?->vendor_id;
                    if (! $vendorId) {
                        $vendorId = $item->productLine?->vendor_id;
                    }
                    if (! $vendorId && $item->manufacturer && $item->style) {
                        $pl = \App\Models\ProductLine::where('manufacturer', trim($item->manufacturer))
                            ->where('name', trim($item->style))
                            ->whereNotNull('vendor_id')
                            ->first();
                        $vendorId = $pl?->vendor_id;
                    }
                    if (! $vendorId && $item->manufacturer) {
                        $mfr   = trim($item->manufacturer);
                        $match = $vendors->first(fn ($v) => strcasecmp(trim($v->company_name), $mfr) === 0)
                              ?? $vendors->first(fn ($v) => stripos(trim($v->company_name), $mfr) !== false);
                        $vendorId = $match?->id;
                    }

                    // Only include items that match this PO's vendor (or have no vendor link)
                    if ($vendorId && (int) $vendorId !== (int) $poVendorId) {
                        continue;
                    }

                    $addableRemainingQtys[$item->id] = $remaining;
                    $roomItems[] = $item;
                }

                if (! empty($roomItems)) {
                    $addableRooms->push(['room' => $room, 'items' => $roomItems]);
                }
            }
        }

        return view('pages.purchase-orders.edit', compact(
            'purchaseOrder', 'vendors', 'warehouseAddress', 'maxQtys', 'addableRooms', 'addableRemainingQtys'
        ));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $isStock = ! $purchaseOrder->sale_id;

        $fulfillmentOptions = $isStock
            ? 'delivery_warehouse,delivery_custom,pickup'
            : 'delivery_site,delivery_warehouse,delivery_custom,pickup';

        $rules = [
            'vendor_id'              => ['required', 'integer', 'exists:vendors,id'],
            'status'                 => ['required', 'in:pending,ordered,received,delivered,cancelled'],
            'vendor_order_number'    => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'fulfillment_method'     => ['required', 'in:' . $fulfillmentOptions],
            'delivery_address'       => ['nullable', 'string', 'max:500'],
            'special_instructions'   => ['nullable', 'string'],
            'pickup_date'            => ['nullable', 'date'],
            'pickup_time'            => ['nullable', 'date_format:H:i'],
            'po_items'               => ['nullable', 'array'],
            'po_items.*.quantity'    => ['nullable', 'numeric', 'min:0'],
            'po_items.*.cost_price'  => ['nullable', 'numeric', 'min:0'],
            'po_items.*.po_notes'    => ['nullable', 'string'],
            'po_items.*.item_name'   => ['nullable', 'string', 'max:255'],
            'po_items.*.unit'        => ['nullable', 'string', 'max:50'],
            'new_items'              => ['nullable', 'array'],
            'new_items.*'            => ['integer', 'exists:sale_items,id'],
            'new_qty'                => ['nullable', 'array'],
            'new_qty.*'              => ['nullable', 'numeric', 'min:0'],
            'new_cost'               => ['nullable', 'array'],
            'new_cost.*'             => ['nullable', 'numeric', 'min:0'],
            'new_po_notes'           => ['nullable', 'array'],
            'new_po_notes.*'         => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        // Gate: moving to "ordered" requires a vendor order number
        if ($data['status'] === 'ordered' && empty($data['vendor_order_number'])) {
            return back()
                ->withErrors(['vendor_order_number' => 'A vendor order number is required to mark this PO as Ordered.'])
                ->withInput();
        }

        $resolvedAddress = $this->resolveDeliveryAddress(
            $data['fulfillment_method'],
            $data['delivery_address'] ?? null,
            $purchaseOrder->sale ?? null,
        );

        $newPickupAt = null;
        if ($data['fulfillment_method'] === 'pickup' && ! empty($data['pickup_date']) && ! empty($data['pickup_time'])) {
            $newPickupAt = \Carbon\Carbon::parse($data['pickup_date'] . ' ' . $data['pickup_time']);
        }

        $wasPickup      = $purchaseOrder->fulfillment_method === 'pickup';
        $isPickup       = $data['fulfillment_method'] === 'pickup';
        $pickupChanged  = $newPickupAt && (! $purchaseOrder->pickup_at || ! $newPickupAt->eq($purchaseOrder->pickup_at));
        $switchedAway   = $wasPickup && ! $isPickup;

        $purchaseOrder->update([
            'vendor_id'              => $data['vendor_id'],
            'status'                 => $data['status'],
            'vendor_order_number'    => $data['vendor_order_number'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'fulfillment_method'     => $data['fulfillment_method'],
            'delivery_address'       => $resolvedAddress,
            'special_instructions'   => $data['special_instructions'] ?? null,
            'pickup_at'              => $newPickupAt,
        ]);

        if ($switchedAway) {
            $this->cancelCalendarEvent($purchaseOrder);
        } elseif ($isPickup && $newPickupAt) {
            if ($purchaseOrder->calendar_event_id) {
                if ($pickupChanged) {
                    $this->syncCalendarUpdate($purchaseOrder);
                }
            } else {
                $this->syncCalendarCreate($purchaseOrder);
            }
        }

        // Validate and update item qty / cost overrides
        if (! empty($data['po_items'])) {
            $purchaseOrder->loadMissing('items.saleItem');

            // Only enforce qty limits for sale-tied POs
            if (! $isStock) {
                $orderedByOthers = $this->orderedQtys($purchaseOrder->sale_id, $purchaseOrder->id);

                $qtyErrors = [];
                foreach ($data['po_items'] as $poItemId => $overrides) {
                    $poItem = $purchaseOrder->items->firstWhere('id', (int) $poItemId);
                    if (! $poItem || ! $poItem->saleItem) {
                        continue;
                    }
                    $saleItem = $poItem->saleItem;
                    $saleQty  = $saleItem->order_qty !== null ? (float) $saleItem->order_qty : (float) $saleItem->quantity;
                    $maxQty   = max(0, $saleQty - ($orderedByOthers[$poItem->sale_item_id] ?? 0));
                    $newQty  = isset($overrides['quantity']) && $overrides['quantity'] !== ''
                        ? (float) $overrides['quantity']
                        : (float) $poItem->quantity;

                    if ($newQty > $maxQty + 0.001) {
                        $qtyErrors["po_items.{$poItemId}.quantity"] = '"' . $poItem->item_name . '" — qty ' . $newQty . ' exceeds max available of ' . $maxQty . ' (sale qty: ' . $saleQty . ').';
                    }
                }

                if (! empty($qtyErrors)) {
                    return back()->withErrors($qtyErrors)->withInput();
                }
            }

            foreach ($data['po_items'] as $poItemId => $overrides) {
                $poItem = $purchaseOrder->items->firstWhere('id', (int) $poItemId);
                if (! $poItem) {
                    continue;
                }

                $fields = [
                    'quantity'   => $overrides['quantity']   ?? $poItem->quantity,
                    'cost_price' => $overrides['cost_price'] ?? $poItem->cost_price,
                    'po_notes'   => $overrides['po_notes']   ?? null,
                ];

                // Stock PO items: also allow editing item_name and unit
                if ($isStock) {
                    if (isset($overrides['item_name']) && $overrides['item_name'] !== '') {
                        $fields['item_name'] = $overrides['item_name'];
                    }
                    if (array_key_exists('unit', $overrides)) {
                        $fields['unit'] = $overrides['unit'] ?: null;
                    }
                }

                $poItem->update($fields);
            }

            // A combined vendor line needs one unit cost — if edits made them differ, split it
            $splitNames = [];
            foreach ($purchaseOrder->items()->whereNotNull('vendor_group')->get()->groupBy('vendor_group') as $group => $members) {
                if ($members->count() > 1 && $members->map(fn ($m) => round((float) $m->cost_price, 3) . '|' . $m->unit)->unique()->count() > 1) {
                    $purchaseOrder->items()->where('vendor_group', $group)->update(['vendor_group' => null]);
                    $splitNames[] = $members->first()->item_name;
                }
            }
            if ($splitNames) {
                session()->flash('warning', 'Unit costs no longer match, so these combined lines were split back into one line per room: ' . implode(', ', $splitNames) . '.');
            }
        }

        // Add new items from sale (sale-linked POs in pending/ordered status only)
        if (
            ! $isStock &&
            ! empty($data['new_items']) &&
            in_array($data['status'], ['pending', 'ordered'])
        ) {
            $purchaseOrder->loadMissing(['items', 'sale']);
            $existingPoSaleItemIds  = $purchaseOrder->items->pluck('sale_item_id')->filter()->toArray();
            $orderedByOthersForNew  = $this->orderedQtys($purchaseOrder->sale_id, $purchaseOrder->id);

            $saleItemsToAdd = $purchaseOrder->sale->items()
                ->where('item_type', 'material')
                ->where('is_removed', false)
                ->whereIn('id', $data['new_items'])
                ->whereNotIn('id', $existingPoSaleItemIds)
                ->orderBy('sort_order')
                ->get();

            $newQtyErrors = [];
            foreach ($saleItemsToAdd as $item) {
                $effectiveQty = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                $remaining    = max(0, $effectiveQty - ($orderedByOthersForNew[$item->id] ?? 0));
                $submittedQty = isset($data['new_qty'][$item->id]) && $data['new_qty'][$item->id] !== ''
                    ? (float) $data['new_qty'][$item->id]
                    : $remaining;

                if ($submittedQty > $remaining + 0.001) {
                    $newQtyErrors["new_qty.{$item->id}"] = '"' . $this->buildItemName($item) . '" — qty ' . $submittedQty . ' exceeds remaining available qty of ' . $remaining . '.';
                }
            }

            if (! empty($newQtyErrors)) {
                return back()->withErrors($newQtyErrors)->withInput();
            }

            $nextSortOrder = ($purchaseOrder->items->max('sort_order') ?? -1) + 1;

            foreach ($saleItemsToAdd as $i => $item) {
                $effectiveQty = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                $remaining    = max(0, $effectiveQty - ($orderedByOthersForNew[$item->id] ?? 0));
                $qty   = isset($data['new_qty'][$item->id]) && $data['new_qty'][$item->id] !== ''
                    ? (float) $data['new_qty'][$item->id]
                    : $remaining;
                $cost  = isset($data['new_cost'][$item->id]) && $data['new_cost'][$item->id] !== ''
                    ? (float) $data['new_cost'][$item->id]
                    : (float) $item->cost_price;
                $notes = ! empty($data['new_po_notes'][$item->id]) ? $data['new_po_notes'][$item->id] : ($item->po_notes ?: null);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'sale_item_id'      => $item->id,
                    'item_name'         => $this->buildItemName($item),
                    'quantity'          => $qty,
                    'unit'              => $item->unit,
                    'po_notes'          => $notes,
                    'cost_price'        => $cost,
                    'sort_order'        => $nextSortOrder + $i,
                ]);
            }
        }

        return redirect()
            ->route('pages.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    // -------------------------------------------------------------------------
    // Split a combined vendor line back into one line per room
    // -------------------------------------------------------------------------

    public function splitVendorGroup(PurchaseOrder $purchaseOrder, string $group)
    {
        $count = $purchaseOrder->items()->where('vendor_group', $group)->update(['vendor_group' => null]);
        abort_if($count === 0, 404);

        return back()->with('success', 'Combined line split back into ' . $count . ' separate lines.');
    }

    // -------------------------------------------------------------------------
    // PDF preview
    // -------------------------------------------------------------------------

    public function previewPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        $pdf      = Pdf::loadView('pdf.purchase-order', compact('purchaseOrder'));
        $filename = $purchaseOrder->po_number . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // -------------------------------------------------------------------------
    // Send email to vendor (Track 1 — shared mailbox)
    // -------------------------------------------------------------------------

    public function sendEmail(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
            'cc'      => ['nullable', 'array'],
            'cc.*'    => ['nullable', 'email'],
        ]);

        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        $user               = auth()->user();
        $mailer             = app(GraphMailService::class);
        $cc                 = array_filter($request->input('cc', []));
        $requestReadReceipt = $request->boolean('request_read_receipt', false);
        $trackingToken      = $requestReadReceipt ? (string) \Illuminate\Support\Str::uuid() : null;
        $pdfContent         = Pdf::loadView('pdf.purchase-order', compact('purchaseOrder'))->output();

        $attachment = [
            'filename' => $purchaseOrder->po_number . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        Storage::disk('local')->put("mail-attachments/purchase_order/{$purchaseOrder->id}.pdf", $pdfContent);
        $pdfUrl = route('pages.mail-attachments.pdf', ['type' => 'purchase_order', 'id' => $purchaseOrder->id]);

        $to      = $request->input('to');
        $subject = $request->input('subject');
        $body    = $request->input('body');

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $to, $subject, $body, 'purchase_order', $attachment, $cc ?: null, null, $purchaseOrder->id, 'purchase_order', $pdfUrl, $requestReadReceipt, $trackingToken)
            : false;

        if (! $sent) {
            $sent = $mailer->send($to, $subject, $body, 'purchase_order', null, $attachment, $cc ?: null, null, $purchaseOrder->id, 'purchase_order', $pdfUrl, $requestReadReceipt, $trackingToken);
        }

        if ($sent) {
            $purchaseOrder->update(['sent_at' => now()]);
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send email. Check the mail log for details.');
        }

        return back()->with('success', 'Purchase order emailed to ' . $request->input('to') . '.');
    }

    // -------------------------------------------------------------------------
    // Soft delete
    // -------------------------------------------------------------------------

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $saleId   = $purchaseOrder->sale_id;
        $poNumber = $purchaseOrder->po_number;

        $purchaseOrder->delete();

        if ($saleId) {
            return redirect()
                ->route('pages.sales.show', $saleId)
                ->with('success', 'Purchase order ' . $poNumber . ' deleted.');
        }

        return redirect()
            ->route('pages.purchase-orders.index')
            ->with('success', 'Purchase order ' . $poNumber . ' deleted.');
    }

    // -------------------------------------------------------------------------
    // Force delete (admin only — permanently removes from DB)
    // -------------------------------------------------------------------------

    public function restore(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->restore();

        $saleId = $purchaseOrder->sale_id;

        if ($saleId) {
            return redirect()
                ->route('pages.sales.show', $saleId)
                ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' restored.');
        }

        return redirect()
            ->route('pages.purchase-orders.index')
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' restored.');
    }

    public function forceDestroy(PurchaseOrder $purchaseOrder)
    {
        $saleId   = $purchaseOrder->sale_id;
        $poNumber = $purchaseOrder->po_number;

        $hasReceipts = \DB::table('inventory_receipts')
            ->where('purchase_order_id', $purchaseOrder->id)
            ->exists();

        if ($hasReceipts) {
            return back()->with('error', 'PO ' . $poNumber . ' cannot be permanently deleted — inventory has been received against it.');
        }

        $purchaseOrder->forceDelete();

        if ($saleId) {
            return redirect()
                ->route('pages.sales.show', $saleId)
                ->with('success', 'Purchase order ' . $poNumber . ' permanently deleted.');
        }

        return redirect()
            ->route('pages.purchase-orders.index')
            ->with('success', 'Purchase order ' . $poNumber . ' permanently deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Sum of PO item quantities per sale_item_id for a given sale,
     * excluding cancelled and soft-deleted POs.
     * Pass $excludePurchaseOrderId to ignore the current PO (used when editing).
     *
     * @return array<int, float>  [sale_item_id => total_ordered_qty]
     */
    private function orderedQtys(int $saleId, ?int $excludePurchaseOrderId = null): array
    {
        return PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($saleId, $excludePurchaseOrderId) {
                $q->where('sale_id', $saleId)
                  ->where('status', '<>', 'cancelled');
                if ($excludePurchaseOrderId) {
                    $q->where('id', '<>', $excludePurchaseOrderId);
                }
            })
            ->selectRaw('sale_item_id, SUM(quantity) as total_ordered')
            ->groupBy('sale_item_id')
            ->pluck('total_ordered', 'sale_item_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function buildItemName(\App\Models\SaleItem $item): string
    {
        $parts = array_filter([
            $item->product_type,
            $item->manufacturer,
            $item->style,
            $item->color_item_number,
        ]);

        return implode(' — ', $parts) ?: 'Material Item';
    }

    private function resolveDeliveryAddress(string $method, ?string $custom, ?Sale $sale): ?string
    {
        return match ($method) {
            'delivery_site'      => $sale?->job_address,
            'delivery_warehouse' => $this->warehouseAddress(),
            'delivery_custom'    => $custom,
            'pickup'             => null,
            default              => null,
        };
    }

    private function warehouseAddress(): string
    {
        $parts = array_filter([
            Setting::get('branding_street'),
            Setting::get('branding_city'),
            Setting::get('branding_province'),
            Setting::get('branding_postal'),
        ]);

        return implode(', ', $parts) ?: '';
    }

    // -------------------------------------------------------------------------
    // Pickup calendar sync helpers (RM – Warehouse group calendar)
    // -------------------------------------------------------------------------

    private function buildPickupEventData(PurchaseOrder $po): array
    {
        $po->loadMissing(['vendor', 'sale']);

        $vars = [
            'po_number'            => $po->po_number,
            'vendor_name'          => $po->vendor?->company_name ?? 'Vendor',
            'sale_number'          => $po->sale?->sale_number ?? '',
            'customer_name'        => $po->sale?->customer_name ?? '',
            'special_instructions' => strip_tags($po->special_instructions ?? ''),
            'pm_name'              => $po->sale?->pm_name ?? '',
            'pm_first_name'        => explode(' ', trim($po->sale?->pm_name ?? ''))[0],
        ];

        $rendered = app(CalendarTemplateService::class)->renderTemplate('po_pickup_calendar', $vars);

        $start = $po->pickup_at;
        $end   = $po->pickup_at->copy()->addHour();

        return [
            'title' => $rendered['title'],
            'notes' => $rendered['notes'],
            'start' => $start,
            'end'   => $end,
        ];
    }

    private function syncCalendarCreate(PurchaseOrder $po): void
    {
        try {
            $account = MicrosoftAccount::where('user_id', auth()->id())
                ->where('is_connected', true)
                ->first();

            if (! $account) {
                Log::info('[PO] No connected Microsoft account — skipping pickup calendar', ['po_id' => $po->id]);
                return;
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('group_id', self::WAREHOUSE_GROUP_ID)
                ->first();

            if (! $calendar) {
                Log::warning('[PO] RM–Warehouse calendar not found for account — skipping', [
                    'po_id'      => $po->id,
                    'account_id' => $account->id,
                ]);
                return;
            }

            $eventData  = $this->buildPickupEventData($po);
            $service    = new GraphCalendarService();
            $externalId = $service->createEvent($account, $calendar, $eventData);
            $localEvent = $service->persistLocalEvent($account, $calendar, $externalId, $eventData, PurchaseOrder::class, $po->id);

            $po->update(['calendar_event_id' => $localEvent->id]);

            Log::info('[PO] Pickup calendar event created on RM–Warehouse', ['po_id' => $po->id]);
        } catch (\Throwable $e) {
            Log::error('[PO] Pickup calendar event creation failed', ['po_id' => $po->id, 'error' => $e->getMessage()]);
            session()->flash('warning', 'PO saved, but the pickup calendar event could not be created. Your Microsoft 365 connection may have expired — check Settings → Integrations to reconnect.');
        }
    }

    private function syncCalendarUpdate(PurchaseOrder $po): void
    {
        if (empty($po->calendar_event_id)) {
            return;
        }

        try {
            $po->loadMissing(['calendarEvent.externalLink']);

            $link = $po->calendarEvent?->externalLink;
            if (! $link) {
                Log::warning('[PO] No ExternalEventLink found for update — skipping', ['po_id' => $po->id]);
                return;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if (! $account) return;

            $eventData = $this->buildPickupEventData($po);
            $service   = new GraphCalendarService();
            $service->updateEvent($account, $link, $eventData);

            $po->calendarEvent?->update([
                'title'       => $eventData['title'],
                'starts_at'   => $eventData['start'],
                'ends_at'     => $eventData['end'],
                'description' => $eventData['notes'],
            ]);

            Log::info('[PO] Pickup calendar event updated', ['po_id' => $po->id]);
        } catch (\Throwable $e) {
            Log::error('[PO] Pickup calendar event update failed', ['po_id' => $po->id, 'error' => $e->getMessage()]);
        }
    }

    private function cancelCalendarEvent(PurchaseOrder $po): void
    {
        if (empty($po->calendar_event_id)) {
            return;
        }

        try {
            $po->loadMissing(['calendarEvent.externalLink']);

            $link = $po->calendarEvent?->externalLink;
            if ($link) {
                $account = MicrosoftAccount::find($link->microsoft_account_id);
                if ($account) {
                    (new GraphCalendarService())->deleteEvent($account, $link);
                }
            }

            $po->calendarEvent?->delete();
            $po->update(['calendar_event_id' => null]);

            Log::info('[PO] Pickup calendar event deleted', ['po_id' => $po->id]);
        } catch (\Throwable $e) {
            Log::error('[PO] Pickup calendar event deletion failed', ['po_id' => $po->id, 'error' => $e->getMessage()]);
        }
    }
}
