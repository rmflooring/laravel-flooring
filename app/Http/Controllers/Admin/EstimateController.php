<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\EstimateRoom;
use App\Models\EstimateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
	public function index(Request $request)
{
    $q        = trim((string) $request->query('q', ''));
    $status   = $request->query('status', '');
    $dateFrom = $request->query('date_from', '');
    $dateTo   = $request->query('date_to', '');

    $estimates = Estimate::query()
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('estimate_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('job_name', 'like', "%{$q}%")
                    ->orWhere('job_no', 'like', "%{$q}%")
                    ->orWhere('pm_name', 'like', "%{$q}%");
            });
        })
        ->when($status !== '', fn($query) => $query->where('status', $status))
        ->when($dateFrom !== '', fn($query) => $query->whereDate('created_at', '>=', $dateFrom))
        ->when($dateTo !== '', fn($query) => $query->whereDate('created_at', '<=', $dateTo))
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

    // NOTE: your statuses are lowercase in validation: draft,sent,approved,rejected
    $statusOptions = ['draft', 'sent', 'approved', 'rejected'];

    return view('admin.estimates.index', compact(
        'estimates',
        'statusOptions',
        'q',
        'status',
        'dateFrom',
        'dateTo' , 
    ));
}
	
    public function store(Request $request)
    {
        $data = $request->validate([
			'opportunity_id' => ['nullable', 'integer'],
            'parent_customer_name' => ['nullable', 'string', 'max:255'],
			'status' => ['required', 'in:draft,sent,revised,approved,rejected'],
            'job_name'             => ['nullable', 'string', 'max:255'],
            'job_number'           => ['nullable', 'string', 'max:255'],
            'job_address'          => ['nullable', 'string', 'max:255'],
            'pm_name'              => ['nullable', 'string', 'max:255'],
			'salesperson_1_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
			'salesperson_2_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes'                => ['nullable', 'string'],
            'estimate_number'      => ['nullable', 'string', 'max:255'],
            'subtotal_materials'   => ['nullable', 'numeric'],
            'subtotal_labour'      => ['nullable', 'numeric'],
            'subtotal_freight'     => ['nullable', 'numeric'],
            'pretax_total'         => ['nullable', 'numeric'],
            'tax_group_id'         => ['nullable', 'integer'],
            'tax_rate_percent'     => ['nullable', 'numeric'],
            'tax_amount'           => ['nullable', 'numeric'],
            'grand_total'          => ['nullable', 'numeric'],
            'rooms'                => ['nullable', 'array'],
            'rooms.*.room_name'    => ['nullable', 'string', 'max:255'],
            'rooms.*.materials'    => ['nullable', 'array'],
            'rooms.*.materials.*.product_type'      => ['nullable', 'string'],
            'rooms.*.materials.*.manufacturer'      => ['nullable', 'string'],
            'rooms.*.materials.*.style'             => ['nullable', 'string'],
            'rooms.*.materials.*.color_item_number' => ['nullable', 'string'],
            'rooms.*.materials.*.po_notes'          => ['nullable', 'string'],
            'rooms.*.materials.*.quantity'          => ['nullable', 'numeric'],
            'rooms.*.materials.*.unit'              => ['nullable', 'string', 'max:50'],
            'rooms.*.materials.*.sell_price'        => ['nullable', 'numeric'],
            'rooms.*.materials.*.notes'             => ['nullable', 'string'],
            'rooms.*.freight'                       => ['nullable', 'array'],
            'rooms.*.freight.*.freight_description' => ['nullable', 'string'],
            'rooms.*.freight.*.quantity'            => ['nullable', 'numeric'],
            'rooms.*.freight.*.unit'                => ['nullable', 'string', 'max:50'],
            'rooms.*.freight.*.sell_price'          => ['nullable', 'numeric'],
            'rooms.*.freight.*.notes'               => ['nullable', 'string'],
            'rooms.*.labour'                        => ['nullable', 'array'],
            'rooms.*.labour.*.labour_type'          => ['nullable', 'string'],
            'rooms.*.labour.*.description'          => ['nullable', 'string'],
            'rooms.*.labour.*.quantity'             => ['nullable', 'numeric'],
            'rooms.*.labour.*.unit'                 => ['nullable', 'string', 'max:50'],
            'rooms.*.labour.*.sell_price'           => ['nullable', 'numeric'],
            'rooms.*.labour.*.notes'                => ['nullable', 'string'],
        ]);

        $rooms = $data['rooms'] ?? [];

        $estimate = DB::transaction(function () use ($data, $rooms) {
            $incomingEstimateNo = trim((string)($data['estimate_number'] ?? ''));
            if ($incomingEstimateNo === '') {
                $year = now()->format('Y');
                $last = Estimate::where('estimate_number', 'like', $year . '-%')
                    ->lockForUpdate()
                    ->orderBy('estimate_number', 'desc')
                    ->value('estimate_number');
                $nextSeq = 1;
                if ($last && preg_match('/^' . $year . '-(\d{3})$/', $last, $m)) {
                    $nextSeq = ((int) $m[1]) + 1;
                }
                $incomingEstimateNo = $year . '-' . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
            }

            $estimate = Estimate::create([
                'estimate_number'    => $incomingEstimateNo,
                'revision_no'        => 0,
				'opportunity_id' => $data['opportunity_id'] ?? null,
                'status'             => 'draft',
                'customer_name'      => $data['parent_customer_name'] ?? null,
                'job_name'           => $data['job_name'] ?? null,
                'job_no'             => $data['job_number'] ?? null,
                'job_address'        => $data['job_address'] ?? null,
                'pm_name'            => $data['pm_name'] ?? null,
				'salesperson_1_employee_id' => $data['salesperson_1_employee_id'] ?? null,
				'salesperson_2_employee_id' => $data['salesperson_2_employee_id'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'subtotal_materials' => (float)($data['subtotal_materials'] ?? 0),
                'subtotal_labour'    => (float)($data['subtotal_labour'] ?? 0),
                'subtotal_freight'   => (float)($data['subtotal_freight'] ?? 0),
                'pretax_total'       => (float)($data['pretax_total'] ?? 0),
                'tax_group_id'       => $data['tax_group_id'] ?? null,
                'tax_rate_percent'   => (float)($data['tax_rate_percent'] ?? 0),
                'tax_amount'         => (float)($data['tax_amount'] ?? 0),
                'grand_total'        => (float)($data['grand_total'] ?? 0),
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            foreach ($rooms as $roomIndex => $room) {
                $roomModel = EstimateRoom::create([
                    'estimate_id'        => $estimate->id,
                    'room_name'          => $room['room_name'] ?? null,
                    'sort_order'         => (int) $roomIndex,
                    'subtotal_materials' => (float)($room['subtotal_materials'] ?? 0),
                    'subtotal_labour'    => (float)($room['subtotal_labour'] ?? 0),
                    'subtotal_freight'   => (float)($room['subtotal_freight'] ?? 0),
                    'room_total'         => (float)($room['room_total'] ?? 0),
                ]);

                foreach (($room['materials'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['product_type', 'quantity', 'sell_price'])) continue;

                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'material',
                        'sort_order'         => (int) $i,
                        'product_type'       => $item['product_type'] ?? null,
                        'manufacturer'       => $item['manufacturer'] ?? null,
                        'style'              => $item['style'] ?? null,
                        'color_item_number'  => $item['color_item_number'] ?? null,
                        'po_notes'           => $item['po_notes'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'unit'               => $item['unit'] ?? null,
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }

                foreach (($room['freight'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;

                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'freight',
                        'sort_order'         => (int) $i,
                        'freight_description' => $item['freight_description'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'unit'               => $item['unit'] ?? null,
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }

                foreach (($room['labour'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['labour_type', 'quantity', 'sell_price', 'description'])) continue;

                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'labour',
                        'sort_order'         => (int) $i,
                        'labour_type'        => $item['labour_type'] ?? null,
                        'description'        => $item['description'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'unit'               => $item['unit'] ?? null,
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }
            }

            return $estimate;
        });

        return redirect()->route('admin.estimates.edit', $estimate->id)
            ->with('success', 'Estimate saved (Draft).')
            ->with('estimate_id', $estimate->id);
    }

	public function edit(Estimate $estimate)
	{
		$estimate->load([
			'rooms' => fn($q) => $q->orderBy('sort_order'),
			'rooms.items' => fn($q) => $q->orderBy('sort_order'),
			'salesperson1Employee',
			'salesperson2Employee',
		]);

		$taxGroups = DB::table('tax_rate_groups')
			->select('tax_rate_groups.*')
			->whereNull('tax_rate_groups.deleted_at')
			->orderBy('tax_rate_groups.name')
			->get();

		$defaultTaxGroupId = DB::table('default_tax')->where('id', 1)->value('tax_rate_group_id');

		// ✅ ADD THIS LINE RIGHT HERE
		$employees = \App\Models\Employee::orderBy('first_name')->orderBy('last_name')->get();

		// ✅ AND RETURN WITH employees INCLUDED
		return view('admin.estimates.edit', compact('estimate', 'taxGroups', 'defaultTaxGroupId', 'employees'));
	}

public function update(Request $request, Estimate $estimate)
{
    $data = $request->validate([
        'parent_customer_name' => ['nullable', 'string', 'max:255'],
        'pm_name'              => ['nullable', 'string', 'max:255'],
        'job_number'           => ['nullable', 'string', 'max:255'],
        'job_name'             => ['nullable', 'string', 'max:255'],
        'job_address'          => ['nullable', 'string', 'max:255'],
		'salesperson_1_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
		'salesperson_2_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        'notes'                => ['nullable', 'string'],
		'status' => ['required', 'in:draft,sent,revised,approved,rejected'],

        'subtotal_materials'   => ['nullable', 'numeric'],
        'subtotal_labour'      => ['nullable', 'numeric'],
        'subtotal_freight'     => ['nullable', 'numeric'],
        'pretax_total'         => ['nullable', 'numeric'],
        'tax_amount'           => ['nullable', 'numeric'],
        'grand_total'          => ['nullable', 'numeric'],
        'tax_group_id'         => ['nullable', 'integer'],
        'tax_rate_percent'     => ['nullable', 'numeric'],

        'rooms'                => ['nullable', 'array'],
        'rooms.*.id'           => ['nullable', 'integer'],
        'rooms.*.room_name'    => ['nullable', 'string', 'max:255'],

        'rooms.*.materials'    => ['nullable', 'array'],
        'rooms.*.freight'      => ['nullable', 'array'],
        'rooms.*.labour'       => ['nullable', 'array'],
    ]);

    DB::transaction(function () use ($estimate, $data) {

        // 1) Update estimate header + totals
        $estimate->forceFill([
            'customer_name'      => $data['parent_customer_name'] ?? $estimate->customer_name,
            'pm_name'            => $data['pm_name'] ?? $estimate->pm_name,
			'salesperson_1_employee_id' => $data['salesperson_1_employee_id'] ?? null,
			'salesperson_2_employee_id' => $data['salesperson_2_employee_id'] ?? null,
            'job_no'             => $data['job_number'] ?? $estimate->job_no,
            'job_name'           => $data['job_name'] ?? $estimate->job_name,
            'job_address'        => $data['job_address'] ?? $estimate->job_address,
            'notes'              => $data['notes'] ?? $estimate->notes,
			'status' => $data['status'],

            'subtotal_materials' => (float)($data['subtotal_materials'] ?? 0),
            'subtotal_labour'    => (float)($data['subtotal_labour'] ?? 0),
            'subtotal_freight'   => (float)($data['subtotal_freight'] ?? 0),
            'pretax_total'       => (float)($data['pretax_total'] ?? 0),
            'tax_amount'         => (float)($data['tax_amount'] ?? 0),
            'grand_total'        => (float)($data['grand_total'] ?? 0),
            'tax_group_id'       => $data['tax_group_id'] ?? $estimate->tax_group_id,
            'tax_rate_percent'   => (float)($data['tax_rate_percent'] ?? 0),

            'updated_by'         => auth()->id(),
        ])->save();

        // 2) Rooms + items
        $rooms = $data['rooms'] ?? [];

        foreach ($rooms as $roomIndex => $roomData) {
            $roomId = $roomData['id'] ?? null;

// Create or load the room
if ($roomId) {
    $room = EstimateRoom::where('id', $roomId)
        ->where('estimate_id', $estimate->id)
        ->firstOrFail();
} else {
    $room = new EstimateRoom();
    $room->estimate_id = $estimate->id;
}

// Save room meta (both update + create)
$room->room_name  = $roomData['room_name'] ?? null;
$room->sort_order = (int)$roomIndex;
$room->save();

// IMPORTANT: use the saved ID for items
$roomId = $room->id;

            // delete all items for this room then re-insert
            EstimateItem::where('estimate_id', $estimate->id)
                ->where('estimate_room_id', $roomId)
                ->delete();

            // MATERIALS
            foreach (($roomData['materials'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['product_type', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'       => $estimate->id,
                    'estimate_room_id'  => $roomId,
                    'item_type'         => 'material',
                    'sort_order'        => (int)$i,
                    'product_type'      => $item['product_type'] ?? null,
                    'manufacturer'      => $item['manufacturer'] ?? null,
                    'style'             => $item['style'] ?? null,
                    'color_item_number' => $item['color_item_number'] ?? null,
                    'po_notes'          => $item['po_notes'] ?? null,
                    'quantity'          => (float)($item['quantity'] ?? 0),
                    'unit'              => $item['unit'] ?? null,
                    'sell_price'        => (float)($item['sell_price'] ?? 0),
                    'line_total'        => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'             => $item['notes'] ?? null,
                ]);
            }

            // FREIGHT
            foreach (($roomData['freight'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'        => $estimate->id,
                    'estimate_room_id'   => $roomId,
                    'item_type'          => 'freight',
                    'sort_order'         => (int)$i,
                    'freight_description'=> $item['freight_description'] ?? null,
                    'quantity'           => (float)($item['quantity'] ?? 0),
                    'unit'               => $item['unit'] ?? null,
                    'sell_price'         => (float)($item['sell_price'] ?? 0),
                    'line_total'         => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'              => $item['notes'] ?? null,
                ]);
            }

            // LABOUR
            foreach (($roomData['labour'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['labour_type', 'description', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'      => $estimate->id,
                    'estimate_room_id' => $roomId,
                    'item_type'        => 'labour',
                    'sort_order'       => (int)$i,
                    'labour_type'      => $item['labour_type'] ?? null,
                    'description'      => $item['description'] ?? null,
                    'quantity'         => (float)($item['quantity'] ?? 0),
                    'unit'             => $item['unit'] ?? null,
                    'sell_price'       => (float)($item['sell_price'] ?? 0),
                    'line_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'            => $item['notes'] ?? null,
                ]);
            }
        }
    });

    return redirect()
        ->route('admin.estimates.edit', $estimate)
        ->with('success', 'Estimate updated.');
}



    private function isRowEmpty(array $row, array $keysToCheck): bool
    {
        foreach ($keysToCheck as $key) {
            if (!empty($row[$key])) {
                return false;
            }
        }
        return true;
    }
	
	public function apiProductTypes()
{
    $rows = \App\Models\ProductType::query()
        ->where('status', 'active')
        ->with([
            'soldByUnit:id,code,label,status',
        ])
        ->orderBy('name')
        ->get(['id', 'name', 'sold_by_unit_id']);

    return response()->json(
        $rows->map(function ($pt) {
            return [
                'id' => $pt->id,
                'name' => $pt->name,
                'sold_by_unit' => $pt->soldByUnit ? [
                    'id' => $pt->soldByUnit->id,
                    'code' => $pt->soldByUnit->code,
                    'label' => $pt->soldByUnit->label,
                ] : null,
            ];
        })
    );
}

public function apiManufacturers(Request $request)
{
    $request->validate([
        'product_type_id' => ['required', 'integer'],
    ]);

    $manufacturers = DB::table('product_lines')
        ->where('product_type_id', (int) $request->product_type_id)
        ->whereNotNull('manufacturer')
        ->where('manufacturer', '!=', '')
        ->distinct()
        ->orderBy('manufacturer')
        ->pluck('manufacturer')
        ->values();

    return response()->json([
        'manufacturers' => $manufacturers,
    ]);
}

public function apiStyles(Request $request)
{
    $productTypeId = (int) $request->query('product_type_id');
    $manufacturer  = trim((string) $request->query('manufacturer'));

    if (!$productTypeId || $manufacturer === '') {
        return response()->json([]);
    }

    // Find product_line IDs matching product type + manufacturer
    $lineIds = DB::table('product_lines')
        ->where('product_type_id', $productTypeId)
        ->whereNotNull('manufacturer')
        ->where('manufacturer', '!=', '')
        ->where('manufacturer', $manufacturer)
        ->pluck('id');

    if ($lineIds->isEmpty()) {
        return response()->json([]);
    }

    // Return styles for those product lines
    $styles = DB::table('product_styles')
        ->whereIn('product_line_id', $lineIds->all())
        ->where('status', 'active')   // remove this line if you don't use 'active'
        ->orderBy('name')
        ->get(['id', 'product_line_id', 'name', 'sku', 'style_number', 'sell_price']);

    return response()->json($styles);
}

	public function apiProductLines(\Illuminate\Http\Request $request)
{
    $productTypeId = (int) $request->query('product_type_id', 0);
    $manufacturer  = trim((string) $request->query('manufacturer', ''));

    if ($productTypeId <= 0 || $manufacturer === '') {
        return response()->json([]);
    }

    $lines = \DB::table('product_lines')
        ->select('id', 'name')
        ->where('status', 'active')
        ->where('product_type_id', $productTypeId)
        ->where('manufacturer', $manufacturer)
        ->orderBy('name')
        ->get();

    return response()->json($lines);
}

}