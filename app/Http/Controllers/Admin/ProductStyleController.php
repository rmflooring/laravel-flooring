<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductLine;  

class ProductStyleController extends Controller
{
	
public function index(ProductLine $product_line)
{
    $styles = $product_line->productStyles()->latest()->paginate(20);

    // Get all product line IDs ordered by ID
    $allIds = ProductLine::orderBy('id')->pluck('id')->toArray();
    $currentIndex = array_search($product_line->id, $allIds); // zero-based index

    $firstId = $allIds[0] ?? null;
    $lastId = $allIds[count($allIds) - 1] ?? null;
    $prevId = $allIds[$currentIndex - 1] ?? null;
    $nextId = $allIds[$currentIndex + 1] ?? null;

    $currentPosition = $currentIndex + 1; // human-friendly (1-based)
    $totalLines = count($allIds);

    return view('admin.product_styles.index', compact(
        'product_line', 'styles', 'firstId', 'prevId', 'nextId', 'lastId',
        'currentPosition', 'totalLines'
    ));
}

	
// Show edit modal (we'll trigger modal from the index page)
public function edit(ProductLine $product_line, $styleId)
{
    $style = $product_line->productStyles()->findOrFail($styleId);

    // We’ll redirect back to index with a flag to open the modal
    return redirect()
        ->route('admin.product_styles.index', $product_line)
        ->with([
            'editStyle' => $style
        ]);
}

// Handle the update
public function update(Request $request, ProductLine $product_line, $styleId)
{
    $style = $product_line->productStyles()->findOrFail($styleId);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'style_number' => 'nullable|string|max:255',
        'color' => 'nullable|string|max:255',
        'pattern' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    $style->update($validated);

    return redirect()->route('admin.product_styles.index', $product_line)
        ->with('success', 'Style updated successfully.');
}

public function store(Request $request, ProductLine $product_line)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'style_number' => 'nullable|string|max:255',
        'color' => 'nullable|string|max:255',
        'pattern' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    // Create the new style for this product line
    $product_line->productStyles()->create($validated);

    return redirect()->route('admin.product_styles.index', $product_line)
        ->with('success', 'Style created successfully.');
}

}
