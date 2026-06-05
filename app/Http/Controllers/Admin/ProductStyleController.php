<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ShopCacheService;
use Illuminate\Http\Request;
use App\Models\ProductLine;
use App\Models\Vendor;

class ProductStyleController extends Controller
{
    public function index(Request $request, ProductLine $product_line)
    {
        $query = $product_line->productStyles()->withCount(['estimateItems', 'saleItems']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('style_number', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('pattern', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', '<>', 'archived');
        }

        $styles = $query->orderBy('name')->paginate(50)->withQueryString();

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
		
		if (request()->wantsJson()) {
    return response()->json(
        $product_line->productStyles()
            ->orderBy('name')
            ->get(['id', 'name', 'use_box_qty', 'units_per', 'cost_price', 'sell_price'])
    );
}

        $vendors = Vendor::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.product_styles.index', compact(
            'product_line',
            'styles',
            'firstId',
            'prevId',
            'nextId',
            'lastId',
            'currentPosition',
            'totalLines',
            'vendors',
        ));
    }

    public function edit(ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->with('photos')->findOrFail($styleId);

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
            'status' => 'required|in:active,inactive,dropped',
            'units_per' => 'nullable|numeric|min:0',
            'use_box_qty' => 'boolean',
            'thickness' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|exists:vendors,id',
            'shop_visible'    => 'boolean',
            'shop_show_price' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['use_box_qty']     = $request->boolean('use_box_qty');
        $validated['shop_visible']    = $request->boolean('shop_visible');
        $validated['shop_show_price'] = $request->boolean('shop_show_price');

        $style->update($validated);

        app(ShopCacheService::class)->bustProductStyle($product_line->id, $product_line->product_type_id);

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
            'status' => 'required|in:active,inactive,dropped',
            'units_per' => 'nullable|numeric|min:0',
            'use_box_qty' => 'boolean',
            'thickness' => 'nullable|string|max:50',
            'vendor_id' => 'nullable|exists:vendors,id',
            'shop_visible'    => 'boolean',
            'shop_show_price' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['use_box_qty']     = $request->boolean('use_box_qty');
        $validated['shop_visible']    = $request->boolean('shop_visible');
        $validated['shop_show_price'] = $request->boolean('shop_show_price');

        $product_line->productStyles()->create($validated);

        app(ShopCacheService::class)->bustProductStyle($product_line->id, $product_line->product_type_id);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style created successfully.');
    }

    public function duplicate(ProductLine $product_line, $style)
    {
        $original = $product_line->productStyles()->findOrFail($style);

        $copy = $product_line->productStyles()->create([
            'name'         => 'Copy of ' . $original->name,
            'sku'          => null,
            'style_number' => null,
            'color'        => $original->color,
            'pattern'      => $original->pattern,
            'description'  => $original->description,
            'cost_price'   => $original->cost_price,
            'sell_price'   => $original->sell_price,
            'units_per'    => $original->units_per,
            'use_box_qty'  => $original->use_box_qty,
            'thickness'    => $original->thickness,
            'vendor_id'    => $original->vendor_id,
            'status'       => $original->status === 'dropped' ? 'active' : $original->status,
            'created_by'   => auth()->id(),
        ]);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('editStyle', $copy);
    }

    public function archive(ProductLine $product_line, $style)
    {
        $styleModel = $product_line->productStyles()->findOrFail($style);
        $styleModel->update(['status' => 'archived', 'updated_by' => auth()->id()]);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style archived.');
    }

    public function unarchive(ProductLine $product_line, $style)
    {
        $styleModel = $product_line->productStyles()->findOrFail($style);
        $styleModel->update(['status' => 'inactive', 'updated_by' => auth()->id()]);

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style restored to inactive.');
    }

    public function bulkUpdate(Request $request, ProductLine $product_line)
    {
        $request->validate([
            'style_ids'       => 'required|array|min:1',
            'style_ids.*'     => 'integer',
            'cost_price'      => 'nullable|numeric|min:0',
            'sell_price'      => 'nullable|numeric|min:0',
            'status'          => 'nullable|in:active,inactive,dropped',
            'units_per'       => 'nullable|numeric|min:0',
            'thickness'       => 'nullable|string|max:50',
            'shop_visible'    => 'nullable|in:0,1',
            'shop_show_price' => 'nullable|in:0,1',
        ]);

        $updates = ['updated_by' => auth()->id()];

        if ($request->filled('cost_price'))      $updates['cost_price']      = $request->input('cost_price');
        if ($request->filled('sell_price'))      $updates['sell_price']      = $request->input('sell_price');
        if ($request->filled('status'))          $updates['status']          = $request->input('status');
        if ($request->filled('units_per'))       $updates['units_per']       = $request->input('units_per');
        if ($request->filled('thickness'))       $updates['thickness']       = $request->input('thickness');
        if ($request->filled('shop_visible'))    $updates['shop_visible']    = (bool) $request->input('shop_visible');
        if ($request->filled('shop_show_price')) $updates['shop_show_price'] = (bool) $request->input('shop_show_price');

        if (count($updates) <= 1) {
            return redirect()
                ->route('admin.product_styles.index', $product_line)
                ->with('error', 'No fields were filled in — nothing to update.');
        }

        $styleIds = $product_line->productStyles()
            ->whereIn('id', $request->input('style_ids'))
            ->pluck('id');

        if ($styleIds->isEmpty()) {
            return redirect()
                ->route('admin.product_styles.index', $product_line)
                ->with('error', 'No valid styles selected.');
        }

        $product_line->productStyles()->whereIn('id', $styleIds)->update($updates);

        app(ShopCacheService::class)->bustProductStyle($product_line->id, $product_line->product_type_id);

        $count = $styleIds->count();
        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', "Bulk update applied to {$count} " . ($count === 1 ? 'style' : 'styles') . '.');
    }

    public function destroy(ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        if ($style->hasActivity()) {
            return redirect()
                ->route('admin.product_styles.index', $product_line)
                ->with('error', 'This style cannot be deleted — it has been used in estimates or sales.');
        }

        $style->delete();

        return redirect()
            ->route('admin.product_styles.index', $product_line)
            ->with('success', 'Style permanently deleted.');
    }
}
