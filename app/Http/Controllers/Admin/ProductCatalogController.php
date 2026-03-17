<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $mode   = $request->input('mode', 'styles'); // 'lines' or 'styles'

        $lineResults  = collect();
        $styleResults = collect();

        if ($search !== '') {
            if ($mode === 'lines') {
                $lineResults = ProductLine::with(['productType', 'vendorRelation'])
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('manufacturer', 'like', "%{$search}%")
                          ->orWhere('model', 'like', "%{$search}%")
                          ->orWhere('collection', 'like', "%{$search}%")
                          ->orWhereHas('productType', fn($pt) => $pt->where('name', 'like', "%{$search}%"))
                          ->orWhereHas('vendorRelation', fn($v) => $v->where('company_name', 'like', "%{$search}%"));
                    })
                    ->orderBy('name')
                    ->paginate(30)
                    ->withQueryString();
            } else {
                $styleResults = ProductStyle::with(['productLine.productType', 'productLine.vendorRelation'])
                    ->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('color', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%")
                          ->orWhere('style_number', 'like', "%{$search}%")
                          ->orWhere('pattern', 'like', "%{$search}%");
                    })
                    ->orderBy('name')
                    ->paginate(50)
                    ->withQueryString();
            }
        }

        $stats = [
            'types'  => ProductType::count(),
            'lines'  => ProductLine::count(),
            'styles' => ProductStyle::count(),
            'active_lines'  => ProductLine::where('status', 'active')->count(),
            'active_styles' => ProductStyle::where('status', 'active')->count(),
        ];

        return view('admin.products.index', compact(
            'search',
            'mode',
            'lineResults',
            'styleResults',
            'stats',
        ));
    }
}
