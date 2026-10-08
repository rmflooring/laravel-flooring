<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** rmflooring.ca → FM: a paid online order. Auth: bearer WEB_ORDERS_API_KEY. */
class WebOrderController extends Controller
{
    public function store(Request $request, WebOrderService $service): JsonResponse
    {
        $order = $request->validate([
            'order_number'               => 'required|string|max:40',
            'customer.name'              => 'required|string|max:255',
            'customer.email'             => 'nullable|email|max:255',
            'customer.phone'             => 'required|string|max:30',
            'items'                      => 'required|array|min:1|max:50',
            'items.*.label'              => 'required|string|max:255',
            'items.*.brand'              => 'nullable|string|max:255',
            'items.*.product_line_id'    => 'nullable|integer',
            'items.*.product_style_id'   => 'nullable|integer',
            'items.*.quantity'           => 'required|numeric|min:0.01',
            'items.*.unit'               => 'nullable|string|max:20',
            'items.*.unit_price'         => 'required|numeric|min:0',
            'items.*.boxes'              => 'nullable|integer|min:1',
            'items.*.return_policy'      => 'nullable|string|max:500',
            'subtotal'                   => 'required|numeric|min:0',
            'tax_amount'                 => 'required|numeric|min:0',
            'total'                      => 'required|numeric|min:0',
            'payment.reference'          => 'required|string|max:255',
            'payment.amount'             => 'required|numeric|min:0',
            'pickup.location'            => 'nullable|string|max:255',
            'pickup.hours'               => 'nullable|string|max:255',
            'notes'                      => 'nullable|string|max:1000',
        ]);

        $sale = $service->create($order);

        return response()->json(['success' => true, 'sale_id' => $sale->id, 'sale_number' => $sale->sale_number]);
    }
}
