<?php

namespace App\Http\Controllers;

use App\Models\ProductLine;

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

        $title = $productLine->manufacturer
            ? $productLine->manufacturer . ' — ' . $productLine->name
            : $productLine->name;

        return view('public.product-line-scan', compact('productLine', 'title'));
    }
}
