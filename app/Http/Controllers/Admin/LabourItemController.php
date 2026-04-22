<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitMeasure;
use App\Models\LabourType;
use App\Models\LabourItem;
use Illuminate\Http\Request;

class LabourItemController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('show_archived');

        $query = $showArchived
            ? LabourItem::withTrashed()->with(['unitMeasure', 'labourType'])
            : LabourItem::with(['unitMeasure', 'labourType']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('labour_type_id')) {
            $query->where('labour_type_id', $request->labour_type_id);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $labourItems = $query
            ->orderBy('description')
            ->paginate($perPage)
            ->withQueryString();

        $unitMeasures = UnitMeasure::query()->orderBy('label')->get(['id', 'label']);
        $labourTypes  = LabourType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.labour_items.index', compact('labourItems', 'unitMeasures', 'labourTypes', 'showArchived'));
    }

    public function create()
    {
        $unitMeasures = UnitMeasure::query()->orderBy('label')->get(['id', 'label']);
        $labourTypes  = LabourType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.labour_items.create', compact('unitMeasures', 'labourTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'labour_type_id'  => 'required|exists:labour_types,id',
            'description'     => 'required|string|max:255',
            'notes'           => 'nullable|string',
            'cost'            => 'required|numeric|min:0',
            'sell'            => 'required|numeric|min:0',
            'unit_measure_id' => 'required|exists:unit_measures,id',
            'status'          => 'required|in:Active,Inactive,Needs Update',
        ]);

        LabourItem::create($validated);

        return redirect()->route('admin.labour_items.index')
            ->with('success', 'Labour item created.');
    }

    public function edit(LabourItem $labourItem)
    {
        $unitMeasures = UnitMeasure::query()->orderBy('label')->get(['id', 'label']);
        $labourTypes  = LabourType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.labour_items.edit', compact('labourItem', 'unitMeasures', 'labourTypes'));
    }

    public function update(Request $request, LabourItem $labourItem)
    {
        $validated = $request->validate([
            'labour_type_id'  => 'required|exists:labour_types,id',
            'description'     => 'required|string|max:255',
            'notes'           => 'nullable|string',
            'cost'            => 'required|numeric|min:0',
            'sell'            => 'required|numeric|min:0',
            'unit_measure_id' => 'required|exists:unit_measures,id',
            'status'          => 'required|in:Active,Inactive,Needs Update',
        ]);

        $labourItem->update($validated);

        return redirect()->route('admin.labour_items.index')
            ->with('success', 'Labour item updated.');
    }

    public function destroy(LabourItem $labourItem)
    {
        $labourItem->delete();

        return redirect()->route('admin.labour_items.index')
            ->with('success', "Labour item \"{$labourItem->description}\" archived.");
    }

    public function restore(int $id)
    {
        $labourItem = LabourItem::withTrashed()->findOrFail($id);
        $labourItem->restore();

        return redirect()->route('admin.labour_items.index', ['show_archived' => 1])
            ->with('success', "Labour item \"{$labourItem->description}\" restored.");
    }

    public function forceDestroy(int $id)
    {
        $labourItem = LabourItem::withTrashed()->findOrFail($id);
        $description = $labourItem->description;
        $labourItem->forceDelete();

        return redirect()->route('admin.labour_items.index', ['show_archived' => 1])
            ->with('success', "Labour item \"{$description}\" permanently deleted.");
    }

    public function apiIndex(Request $request)
    {
        $labourTypeId = $request->get('labour_type_id');

        $query = LabourItem::query()
            ->with(['unitMeasure'])
            ->orderBy('description');

        if ($labourTypeId) {
            $query->where('labour_type_id', $labourTypeId);
        }

        return $query->get()->map(function ($item) {
            return [
                'id'          => $item->id,
                'description' => $item->description,
                'unit_code'   => optional($item->unitMeasure)->code ?? optional($item->unitMeasure)->label ?? '',
                'cost'        => (string) $item->cost,
                'sell'        => (string) $item->sell,
                'notes'       => (string) ($item->notes ?? ''),
            ];
        });
    }
}
