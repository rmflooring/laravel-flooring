<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\WebOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sales → Web Orders: paid online orders from rmflooring.ca awaiting confirmation / pickup. */
class WebOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');

        $orders = Sale::where('channel', 'web')
            ->when($status === 'open', fn ($q) => $q->whereIn('web_status', ['new', 'confirmed', 'ready']))
            ->when(array_key_exists($status, Sale::WEB_STATUSES), fn ($q) => $q->where('web_status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $counts = Sale::where('channel', 'web')->selectRaw('web_status, count(*) as n')->groupBy('web_status')->pluck('n', 'web_status');

        return view('pages.web-orders.index', compact('orders', 'status', 'counts'));
    }

    public function show(Sale $sale): View
    {
        abort_unless($sale->channel === 'web', 404);
        $sale->load(['rooms.items', 'invoices.payments', 'customer']);

        return view('pages.web-orders.show', ['sale' => $sale]);
    }

    public function update(Request $request, Sale $sale, WebOrderService $service): RedirectResponse
    {
        $to = $request->validate(['status' => 'required|in:confirmed,ready,picked_up,refunded'])['status'];

        try {
            $service->transition($sale, $to);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order ' . $sale->web_order_number . ' → ' . Sale::WEB_STATUSES[$to] . '. The customer has been notified.');
    }
}
