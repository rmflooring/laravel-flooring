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

        $allIds = ProductLine::orderBy('id')->pluck('id')->toArray();
        $currentIndex = array_search($product_line->id, $allIds, true); // strict

        $firstId = $allIds[0] ?? null;
        $lastId  = !empty($allIds) ? $allIds[count($allIds) - 1] : null;

        $prevId = null;
        $nextId = null;

        if ($currentIndex !== false) {
            $prevId = $allIds[$currentIndex - 1] ?? null;
            $nextId = $allIds[$currentIndex + 1] ?? null;
        }

        $currentPosition = ($currentIndex !== false ? $currentIndex + 1 : 1);
        $totalLines = count($allIds);

        return view('admin.product_styles.index', compact(
            'product_line',
            'styles',
            'firstId',
            'prevId',
            'nextId',
            'lastId',
            'currentPosition',
            'totalLines'
        ));
    }

    public function edit(ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with(['editStyle' => $style]);
    }

    public function update(Request $request, ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'style_number' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'pattern' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['updated_by'] = auth()->id();

        $style->update($validated);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style updated successfully.');
    }

    public function store(Request $request, ProductLine $product_line)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255',
            'style_number' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'pattern' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['created_by'] = auth()->id();

        $product_line->productStyles()->create($validated);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style created successfully.');
    }

    public function destroy(ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);
        $style->delete();

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style deleted successfully.');
    }
}
