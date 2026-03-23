<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleChangeOrder;
use App\Services\ChangeOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChangeOrderController extends Controller
{
    public function __construct(private ChangeOrderService $service) {}

    public function create(Sale $sale)
    {
        $this->authorizeCoCreate($sale);

        return view('pages.change-orders.create', compact('sale'));
    }

    public function store(Request $request, Sale $sale)
    {
        $this->authorizeCoCreate($sale);

        $request->validate([
            'title'  => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:1000',
            'notes'  => 'nullable|string|max:2000',
        ]);

        $co = $this->service->create($sale, $request->only(['title', 'reason', 'notes']), auth()->id());

        return redirect()
            ->route('pages.sales.change-orders.show', [$sale, $co])
            ->with('success', "Change Order {$co->co_number} created. You can now edit the sale items.");
    }

    public function show(Sale $sale, SaleChangeOrder $changeOrder)
    {
        abort_unless($changeOrder->sale_id === $sale->id, 404);

        $delta = $this->service->calculateDelta($changeOrder);

        return view('pages.change-orders.show', compact('sale', 'changeOrder', 'delta'));
    }

    public function approve(Request $request, Sale $sale, SaleChangeOrder $changeOrder)
    {
        abort_unless($changeOrder->sale_id === $sale->id, 404);
        abort_unless(in_array($changeOrder->status, ['draft', 'sent']), 422);

        $this->service->approve($changeOrder, auth()->id());

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', "Change Order {$changeOrder->co_number} approved. Sale re-locked at revised total.");
    }

    public function cancel(Request $request, Sale $sale, SaleChangeOrder $changeOrder)
    {
        abort_unless($changeOrder->sale_id === $sale->id, 404);
        abort_unless(in_array($changeOrder->status, ['draft', 'sent']), 422);

        $this->service->revert($changeOrder, auth()->id());

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', "Change Order {$changeOrder->co_number} cancelled. Sale items reverted to original.");
    }

    public function previewPdf(Sale $sale, SaleChangeOrder $changeOrder)
    {
        abort_unless($changeOrder->sale_id === $sale->id, 404);

        $delta = $this->service->calculateDelta($changeOrder);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.change-order', compact('sale', 'changeOrder', 'delta'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("CO-{$changeOrder->co_number}.pdf");
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function authorizeCoCreate(Sale $sale): void
    {
        // Must be approved or change_in_progress is not already open with another CO
        if (! in_array($sale->status, ['approved'])) {
            abort(422, 'A Change Order can only be created on an approved sale.');
        }

        // Gate: no ordered/received POs
        $blockedPos = $sale->purchaseOrders()
            ->whereNotIn('status', ['cancelled'])
            ->withTrashed()
            ->where(function ($q) {
                $q->whereIn('status', ['ordered', 'received'])
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('deleted_at')->whereIn('status', ['ordered', 'received']);
                  });
            })
            ->exists();

        // Simpler: block if any non-cancelled PO/WO exists (pending is safe)
        $hasPendingPos = $sale->purchaseOrders()
            ->whereNotIn('status', ['cancelled'])
            ->where('status', '<>', 'pending')
            ->withTrashed()
            ->exists();

        $hasActiveWos = $sale->workOrders()
            ->whereNotIn('status', ['cancelled'])
            ->where('status', '<>', 'created')
            ->withTrashed()
            ->exists();

        if ($hasPendingPos || $hasActiveWos) {
            abort(422, 'Cannot create a Change Order: a Purchase Order or Work Order has already been actioned for this sale.');
        }

        // Any non-cancelled PO in pending is fine — but ordered/received is blocked even if deleted
        $orderedPoExists = $sale->purchaseOrders()
            ->withTrashed()
            ->whereIn('status', ['ordered', 'received'])
            ->exists();

        $scheduledWoExists = $sale->workOrders()
            ->withTrashed()
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->exists();

        if ($orderedPoExists || $scheduledWoExists) {
            abort(422, 'Cannot create a Change Order: an ordered Purchase Order or scheduled Work Order exists for this sale.');
        }
    }
}
