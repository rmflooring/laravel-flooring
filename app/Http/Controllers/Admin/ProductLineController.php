<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductLineController extends Controller
{
    /**
     * Display a listing of the product lines.
     */
    public function index()
    {
        $lines = ProductLine::with('productType')->latest()->paginate(20);
        return view('admin.product_lines.index', compact('lines'));
    }

    /**
     * Show the form for creating a new product line.
     */
    public function create()
    {
        $types = ProductType::where('status', 'active')->get();
        return view('admin.product_lines.create', compact('types'));
    }

    /**
     * Store a newly created product line in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_type_id' => 'required|exists:product_types,id',
            'name'           => 'required|string|max:255',
            'status'         => 'required|in:active,inactive',
            'vendor'         => 'nullable|string|max:255',
            'manufacturer'   => 'nullable|string|max:255',
            'model'          => 'nullable|string|max:255',
            'collection'     => 'nullable|string|max:255',
        ]);

        ProductLine::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.product-lines.index')
            ->with('success', 'Product line created successfully.');
    }

    /**
     * Show the form for editing the specified product line.
     */
    public function edit($id)
    {
        $line = ProductLine::findOrFail($id);
        $types = ProductType::where('status', 'active')->get();

        return view('admin.product_lines.edit', compact('line', 'types'));
    }

    /**
     * Update the specified product line in storage.
     */
    public function update(Request $request, $id)
    {
        $line = ProductLine::findOrFail($id);

        $validated = $request->validate([
            'product_type_id' => 'required|exists:product_types,id',
            'name'           => 'required|string|max:255',
            'status'         => 'required|in:active,inactive',
            'vendor'         => 'nullable|string|max:255',
            'manufacturer'   => 'nullable|string|max:255',
            'model'          => 'nullable|string|max:255',
            'collection'     => 'nullable|string|max:255',
        ]);

        $line->update([
            ...$validated,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.product-lines.index')
            ->with('success', 'Product line updated successfully.');
    }

    /**
     * Remove the specified product line from storage.
     */
    public function destroy($id)
    {
        $line = ProductLine::findOrFail($id);
        $line->delete();

        return redirect()->route('admin.product-lines.index')
            ->with('success', 'Product line deleted successfully.');
    }
}
