<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductLineController extends Controller
{
    /**
     * Display a listing of the product lines.
     */
    public function index()
{
    $lines = ProductLine::with(['productType', 'vendorRelation']) // ← add 'vendorRelation'
        ->latest()
        ->paginate(20);

    return view('admin.product_lines.index', compact('lines'));
}

    /**
     * Show the form for creating a new product line.
     */
    public function create()
{
    // Load active product types (as you had)
    $types = ProductType::where('status', 'active')->get();

    // Load all vendors, sorted by name for the dropdown
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);

    // Pass both to the view
    return view('admin.product_lines.create', compact('types', 'vendors'));
}

    /**
     * Store a newly created product line in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'vendor_id' => 'nullable|exists:vendors,id',  // ← NEW: validate as foreign key
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
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
    $types = ProductType::where('status', 'active')->get();
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);

    return view('admin.product_lines.edit', compact('product_line', 'types', 'vendors'));
}

public function update(Request $request, ProductLine $product_line)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'vendor_id' => 'nullable|exists:vendors,id',  // ← NEW
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
    ]);

    $product_line->update([
        ...$validated,
        'updated_by' => Auth::id(),
    ]);

    return redirect()->route('admin.product_lines.index')
        ->with('success', 'Product line updated successfully.');
}

    /**
     * Remove the specified product line from storage.
     */
    public function destroy($id)
    {
        $line = ProductLine::findOrFail($id);
        $line->delete();

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line deleted successfully.');
    }
}
