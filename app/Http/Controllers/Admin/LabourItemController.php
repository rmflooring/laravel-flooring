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
        $query = LabourItem::with(['unitMeasure', 'labourType']);

        // Search (matches your CustomerController style)
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('labour_type_id')) {
            $query->where('labour_type_id', $request->labour_type_id);
        }

        if ($request->filled('unit_measure_id')) {
            $query->where('unit_measure_id', $request->unit_measure_id);
        }

        // Per-page (optional but handy)
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $labourItems = $query
            ->orderBy('description')
            ->paginate($perPage)
            ->withQueryString();

        // Dropdown data for filters / create/edit
        $unitMeasures = UnitMeasure::query()->orderBy('label')->get(['id', 'label']);
        $labourTypes  = LabourType::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.labour_items.index', compact('labourItems', 'unitMeasures', 'labourTypes'));
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
            'labour_type_id'   => 'required|exists:labour_types,id',   // ✅ make consistent with update()
            'description'      => 'required|string|max:255',
            'notes'            => 'nullable|string',
            'cost'             => 'required|numeric|min:0',
            'sell'             => 'required|numeric|min:0',
            'unit_measure_id'  => 'required|exists:unit_measures,id',
            'status'           => 'required|in:Active,Inactive,Needs Update',
        ]);

        LabourItem::create($validated);

        return redirect()->route('admin.labour_items.index')
            ->with('success', 'Labour Item created!');
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
            'labour_type_id'   => 'required|exists:labour_types,id',
            'description'      => 'required|string|max:255',
            'notes'            => 'nullable|string',
            'cost'             => 'required|numeric|min:0',
            'sell'             => 'required|numeric|min:0',
            'unit_measure_id'  => 'required|exists:unit_measures,id',
            'status'           => 'required|in:Active,Inactive,Needs Update',
        ]);

        $labourItem->update($validated);

        return redirect()->route('admin.labour_items.index')
            ->with('success', 'Labour Item updated successfully!');
    }
}
