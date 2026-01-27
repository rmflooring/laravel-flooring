<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductStyle;
use App\Models\ProductLine;
use Illuminate\Http\Request;

class ProductPricingController extends Controller
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'product_style_id' => ['required', 'integer'],
            'product_line_id'  => ['required', 'integer'],
        ]);

        $style = ProductStyle::query()->select('id', 'sell_price')->findOrFail((int) $data['product_style_id']);
        $line  = ProductLine::query()->select('id', 'default_sell_price')->findOrFail((int) $data['product_line_id']);

        $stylePrice = $style->sell_price; // may be null
        $linePrice  = $line->default_sell_price; // not null

        $finalPrice = $stylePrice !== null ? $stylePrice : $linePrice;
        $source = $stylePrice !== null ? 'style' : 'line';

        return response()->json([
            'sell_price' => (float) $finalPrice,
            'source' => $source,
            'style_sell_price' => $stylePrice !== null ? (float) $stylePrice : null,
            'line_default_sell_price' => (float) $linePrice,
        ]);
    }
}
