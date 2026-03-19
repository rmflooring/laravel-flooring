<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\UnitMeasure;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductLineController extends Controller
{
    /**
     * Display a listing of the product lines.
     */
public function index(Request $request)
{
    $query = ProductLine::query()
        ->with(['productType', 'vendorRelation']); // keep your view working

    // Search
    if ($request->filled('search')) {
        $search = trim((string) $request->search);

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('manufacturer', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%")
              ->orWhere('collection', 'like', "%{$search}%")
              ->orWhereHas('productType', function ($pt) use ($search) {
                  $pt->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('vendorRelation', function ($v) use ($search) {
                  $v->where('company_name', 'like', "%{$search}%");
              })
              ->orWhereHas('productStyles', function ($s) use ($search) {
                  $s->where('name', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('style_number', 'like', "%{$search}%")
                    ->orWhere('pattern', 'like', "%{$search}%");
              });
        });
    }

    // Filters — hide archived by default unless explicitly requested
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    } else {
        $query->where('status', '<>', 'archived');
    }

    if ($request->filled('product_type_id')) {
        $query->where('product_type_id', $request->product_type_id);
    }

    if ($request->filled('vendor_id')) {
        $query->where('vendor_id', $request->vendor_id);
    }

    // Per page
    $perPage = (int) $request->get('per_page', 15);
    if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
        $perPage = 15;
    }

    $lines = $query
        ->withCount(['estimateItems', 'saleItems'])
        ->orderBy('id', 'desc')
        ->paginate($perPage)
        ->withQueryString(); // critical: keeps filters while paging

    // Dropdown data for filters
    $productTypes = ProductType::orderBy('name')->get(['id', 'name']);
    $vendors      = Vendor::orderBy('company_name')->get(['id', 'company_name']);

    return view('admin.product_lines.index', compact('lines', 'productTypes', 'vendors', 'perPage'));
}

    /**
     * Show the form for creating a new product line.
     */
    public function create()
{
    $types   = ProductType::where('status', 'active')->with('soldByUnit')->get();
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);
    $units   = UnitMeasure::where('status', 'active')->orderBy('label')->get(['id', 'code', 'label']);

    return view('admin.product_lines.create', compact('types', 'vendors', 'units'));
}

    /**
     * Store a newly created product line in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive,dropped',
        'vendor_id' => 'nullable|exists:vendors,id',
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
        'default_cost_price' => 'nullable|numeric|min:0',
        'default_sell_price' => 'nullable|numeric|min:0',
        'unit_id' => 'nullable|exists:unit_measures,id',
        'width' => 'nullable|numeric|min:0',
        'length' => 'nullable|numeric|min:0',
    ]);

    ProductLine::create([
        ...$validated,
        'created_by' => Auth::id(),
    ]);

    return redirect()->route('admin.product_lines.index')
        ->with('success', 'Product line created successfully.');
}

    /**
     * Show the form for editing the specified product line.
     */
public function edit(ProductLine $product_line)
{
    $types   = ProductType::where('status', 'active')->with('soldByUnit')->get();
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);
    $units   = UnitMeasure::where('status', 'active')->orderBy('label')->get(['id', 'code', 'label']);

    return view('admin.product_lines.edit', compact('product_line', 'types', 'vendors', 'units'));
}

public function update(Request $request, ProductLine $product_line)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive,dropped',
        'vendor_id' => 'nullable|exists:vendors,id',
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
        'default_cost_price' => 'nullable|numeric|min:0',
        'default_sell_price' => 'nullable|numeric|min:0',
        'unit_id' => 'nullable|exists:unit_measures,id',
        'width' => 'nullable|numeric|min:0',
        'length' => 'nullable|numeric|min:0',
    ]);

    $product_line->update([
        ...$validated,
        'updated_by' => Auth::id(),
    ]);

    return redirect()->route('admin.product_lines.index')
        ->with('success', 'Product line updated successfully.');
}

    public function destroy(ProductLine $product_line)
    {
        if ($product_line->hasActivity()) {
            return redirect()->route('admin.product_lines.index')
                ->with('error', 'This product line cannot be deleted — it has been used in estimates or sales.');
        }

        $product_line->delete();

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line permanently deleted.');
    }

    public function archive(ProductLine $product_line)
    {
        $product_line->update(['status' => 'archived', 'updated_by' => Auth::id()]);

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line archived.');
    }

    public function unarchive(ProductLine $product_line)
    {
        $product_line->update(['status' => 'inactive', 'updated_by' => Auth::id()]);

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line restored to inactive.');
    }
}
