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
        'dateTo'
    ));
}
	
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_customer_name' => ['nullable', 'string', 'max:255'],
            'job_name'             => ['nullable', 'string', 'max:255'],
            'job_number'           => ['nullable', 'string', 'max:255'],
            'job_address'          => ['nullable', 'string', 'max:255'],
            'pm_name'              => ['nullable', 'string', 'max:255'],
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
                'status'             => 'draft',
                'customer_name'      => $data['parent_customer_name'] ?? null,
                'job_name'           => $data['job_name'] ?? null,
                'job_no'             => $data['job_number'] ?? null,
                'job_address'        => $data['job_address'] ?? null,
                'pm_name'            => $data['pm_name'] ?? null,
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
        ]);

        return view('admin.estimates.edit', compact('estimate'));
    }

    public function update(Request $request, Estimate $estimate)
    {
        $data = $request->validate([
            'parent_customer_name' => ['nullable', 'string', 'max:255'],
            'job_name'             => ['nullable', 'string', 'max:255'],
            'job_number'           => ['nullable', 'string', 'max:255'],
            'job_address'          => ['nullable', 'string', 'max:255'],
            'pm_name'              => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string'],
            'status'               => ['required', 'in:draft,sent,approved,rejected'],
            'rooms'                => ['nullable', 'array'],
            'rooms.*.id'           => ['nullable', 'integer', 'exists:estimate_rooms,id'],
            'rooms.*.room_name'    => ['nullable', 'string', 'max:255'],
            'rooms.*.materials'    => ['nullable', 'array'],
            'rooms.*.materials.*.id'                => ['nullable', 'integer', 'exists:estimate_items,id'],
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
            'rooms.*.freight.*.id'                  => ['nullable', 'integer', 'exists:estimate_items,id'],
            'rooms.*.freight.*.freight_description' => ['nullable', 'string'],
            'rooms.*.freight.*.quantity'            => ['nullable', 'numeric'],
            'rooms.*.freight.*.unit'                => ['nullable', 'string', 'max:50'],
            'rooms.*.freight.*.sell_price'          => ['nullable', 'numeric'],
            'rooms.*.freight.*.notes'               => ['nullable', 'string'],
            'rooms.*.labour'                        => ['nullable', 'array'],
            'rooms.*.labour.*.id'                   => ['nullable', 'integer', 'exists:estimate_items,id'],
            'rooms.*.labour.*.labour_type'          => ['nullable', 'string'],
            'rooms.*.labour.*.description'          => ['nullable', 'string'],
            'rooms.*.labour.*.quantity'             => ['nullable', 'numeric'],
            'rooms.*.labour.*.unit'                 => ['nullable', 'string', 'max:50'],
            'rooms.*.labour.*.sell_price'           => ['nullable', 'numeric'],
            'rooms.*.labour.*.notes'                => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($estimate, $data) {
            $estimate->update([
                'customer_name' => $data['parent_customer_name'] ?? $estimate->customer_name,
                'job_name'      => $data['job_name'] ?? $estimate->job_name,
                'job_no'        => $data['job_number'] ?? $estimate->job_no,
                'job_address'   => $data['job_address'] ?? $estimate->job_address,
                'pm_name'       => $data['pm_name'] ?? $estimate->pm_name,
                'notes'         => $data['notes'] ?? $estimate->notes,
                'status'        => $data['status'],
                'updated_by'    => auth()->id(),
            ]);

            $estimate->items()->delete();
            $estimate->rooms()->delete();

            $rooms = $data['rooms'] ?? [];

            foreach ($rooms as $roomIndex => $room) {
                if (!empty($room['_delete']) && $room['_delete'] === '1') continue;

                $roomModel = EstimateRoom::create([
                    'estimate_id'        => $estimate->id,
                    'room_name'          => $room['room_name'] ?? null,
                    'sort_order'         => (int) $roomIndex,
                    'subtotal_materials' => 0,
                    'subtotal_labour'    => 0,
                    'subtotal_freight'   => 0,
                    'room_total'         => 0,
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
                        'line_total'         => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }

                foreach (($room['freight'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;

                    EstimateItem::create([
                        'estimate_id'         => $estimate->id,
                        'estimate_room_id'    => $roomModel->id,
                        'item_type'           => 'freight',
                        'sort_order'          => (int) $i,
                        'freight_description' => $item['freight_description'] ?? null,
                        'quantity'            => (float)($item['quantity'] ?? 0),
                        'unit'                => $item['unit'] ?? null,
                        'sell_price'          => (float)($item['sell_price'] ?? 0),
                        'line_total'          => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }

                foreach (($room['labour'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['labour_type', 'quantity', 'sell_price', 'description'])) continue;

                    EstimateItem::create([
                        'estimate_id'      => $estimate->id,
                        'estimate_room_id' => $roomModel->id,
                        'item_type'        => 'labour',
                        'sort_order'       => (int) $i,
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

            $estimate->load('rooms.items');

            foreach ($estimate->rooms as $room) {
                $mat = (float) $room->items->where('item_type', 'material')->sum('line_total');
                $fre = (float) $room->items->where('item_type', 'freight')->sum('line_total');
                $lab = (float) $room->items->where('item_type', 'labour')->sum('line_total');

                $room->update([
                    'subtotal_materials' => round($mat, 2),
                    'subtotal_freight'   => round($fre, 2),
                    'subtotal_labour'    => round($lab, 2),
                    'room_total'         => round($mat + $fre + $lab, 2),
                ]);
            }

            $totalMat = $estimate->rooms->sum('subtotal_materials');
            $totalFre = $estimate->rooms->sum('subtotal_freight');
            $totalLab = $estimate->rooms->sum('subtotal_labour');
            $pretax   = round($totalMat + $totalFre + $totalLab, 2);

            $estimate->update([
                'subtotal_materials' => round($totalMat, 2),
                'subtotal_freight'   => round($totalFre, 2),
                'subtotal_labour'    => round($totalLab, 2),
                'pretax_total'       => $pretax,
            ]);
        });

        return redirect()->route('admin.estimates.edit', $estimate->id)
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
}