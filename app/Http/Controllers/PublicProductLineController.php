<?php

namespace App\Http\Controllers;

use App\Models\ProductLine;
use App\Models\SampleSet;

class PublicProductLineController extends Controller
{
    public function show(ProductLine $productLine)
    {
        $productLine->load([
            'productType',
            'unit',
            'productStyles' => fn ($q) => $q->where('status', 'active')
                ->orderBy('name')
                ->with(['photos' => fn ($p) => $p->orderByDesc('is_primary')->orderBy('sort_order')]),
        ]);

        $sampleSets = SampleSet::where('product_line_id', $productLine->id)
            ->whereIn('status', ['active', 'checked_out'])
            ->with('activeCheckout')
            ->orderBy('set_id')
            ->get();

        $title = $productLine->manufacturer
            ? $productLine->manufacturer . ' — ' . $productLine->name
            : $productLine->name;

        return view('public.product-line-scan', compact('productLine', 'title', 'sampleSets'));
    }
}
