<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ArController extends Controller
{
    // -------------------------------------------------------------------------
    // Index — invoice list + stat cards
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = Invoice::with(['sale.opportunity.parentCustomer'])
            ->whereNotIn('status', ['voided']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('sale', fn ($s) => $s->where('sale_number', 'like', "%{$search}%")
                      ->orWhere('homeowner_name', 'like', "%{$search}%"))
                  ->orWhereHas('sale.opportunity.parentCustomer', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('due_from')) {
            $query->where('due_date', '>=', $request->due_from);
        }

        if ($request->filled('due_to')) {
            $query->where('due_date', '<=', $request->due_to);
        }

        $invoices = $query->orderBy('due_date')->orderByDesc('id')->paginate(25)->withQueryString();

        // Stat cards
        $baseQuery = Invoice::whereNotIn('status', ['voided']);

        $totalOutstanding = (clone $baseQuery)->whereNotIn('status', ['paid'])->sum('grand_total');
        $totalPaid        = (clone $baseQuery)->where('status', 'paid')->sum('amount_paid');
        $totalOverdue     = (clone $baseQuery)->where('status', 'overdue')->sum('grand_total');
        $dueThisWeek      = (clone $baseQuery)
            ->whereNotIn('status', ['paid', 'overdue'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('grand_total');

        $statuses = [
            'draft'           => 'Draft',
            'sent'            => 'Sent',
            'partially_paid'  => 'Partially Paid',
            'paid'            => 'Paid',
            'overdue'         => 'Overdue',
        ];

        return view('pages.ar.index', compact(
            'invoices', 'statuses',
            'totalOutstanding', 'totalPaid', 'totalOverdue', 'dueThisWeek'
        ));
    }

    // -------------------------------------------------------------------------
    // Aging report
    // -------------------------------------------------------------------------

    public function aging()
    {
        $today = Carbon::today();

        $invoices = Invoice::with(['sale.opportunity.parentCustomer'])
            ->whereNotIn('status', ['voided', 'paid'])
            ->whereNotNull('due_date')
            ->get();

        $buckets = [
            'current'  => ['label' => 'Current (not yet due)', 'invoices' => collect()],
            '1_30'     => ['label' => '1 – 30 days',           'invoices' => collect()],
            '31_60'    => ['label' => '31 – 60 days',          'invoices' => collect()],
            '61_90'    => ['label' => '61 – 90 days',          'invoices' => collect()],
            '90_plus'  => ['label' => '90+ days',              'invoices' => collect()],
        ];

        foreach ($invoices as $invoice) {
            $daysOverdue = $today->diffInDays($invoice->due_date, false) * -1;

            if ($daysOverdue <= 0) {
                $buckets['current']['invoices']->push($invoice);
            } elseif ($daysOverdue <= 30) {
                $buckets['1_30']['invoices']->push($invoice);
            } elseif ($daysOverdue <= 60) {
                $buckets['31_60']['invoices']->push($invoice);
            } elseif ($daysOverdue <= 90) {
                $buckets['61_90']['invoices']->push($invoice);
            } else {
                $buckets['90_plus']['invoices']->push($invoice);
            }
        }

        foreach ($buckets as &$bucket) {
            $bucket['total'] = $bucket['invoices']->sum('balance_due');
        }

        $grandTotal = collect($buckets)->sum('total');

        return view('pages.ar.aging', compact('buckets', 'grandTotal', 'today'));
    }
}
