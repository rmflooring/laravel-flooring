<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArController extends Controller
{
    // -------------------------------------------------------------------------
    // Index — invoice list + stat cards
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        // Bill-to customer priority mirrors QboSyncService::pushInvoice: job site
        // customer, then its parent (or the opportunity's parent customer), then
        // the sale's directly-linked customer, then the sale's free-text name.
        $query = Invoice::with([
                'sale.opportunity.jobSiteCustomer.parent',
                'sale.opportunity.parentCustomer',
                'sale.customer',
            ])
            ->leftJoin('sales', 'sales.id', '=', 'invoices.sale_id')
            ->leftJoin('opportunities', 'opportunities.id', '=', 'sales.opportunity_id')
            ->leftJoin('customers as job_site_customers', 'job_site_customers.id', '=', 'opportunities.job_site_customer_id')
            ->leftJoin('customers as job_site_parents', 'job_site_parents.id', '=', 'job_site_customers.parent_id')
            ->leftJoin('customers as parent_customers', 'parent_customers.id', '=', 'opportunities.parent_customer_id')
            ->leftJoin('customers as sale_customers', 'sale_customers.id', '=', 'sales.customer_id')
            ->select('invoices.*', DB::raw("COALESCE(
                NULLIF(job_site_customers.company_name, ''), job_site_customers.name,
                NULLIF(job_site_parents.company_name, ''), job_site_parents.name,
                NULLIF(parent_customers.company_name, ''), parent_customers.name,
                NULLIF(sale_customers.company_name, ''), sale_customers.name,
                sales.customer_name, sales.homeowner_name
            ) as customer_sort_name"))
            ->whereNotIn('invoices.status', ['voided']);

        if ($request->filled('status')) {
            $query->where('invoices.status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('invoices.invoice_number', 'like', "%{$search}%")
                  ->orWhere('sales.sale_number', 'like', "%{$search}%")
                  ->orWhere('sales.homeowner_name', 'like', "%{$search}%")
                  ->orWhere('sales.customer_name', 'like', "%{$search}%")
                  ->orWhere('job_site_customers.company_name', 'like', "%{$search}%")
                  ->orWhere('job_site_customers.name', 'like', "%{$search}%")
                  ->orWhere('parent_customers.company_name', 'like', "%{$search}%")
                  ->orWhere('parent_customers.name', 'like', "%{$search}%")
                  ->orWhere('sale_customers.company_name', 'like', "%{$search}%")
                  ->orWhere('sale_customers.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('due_from')) {
            $query->where('invoices.due_date', '>=', $request->due_from);
        }

        if ($request->filled('due_to')) {
            $query->where('invoices.due_date', '<=', $request->due_to);
        }

        // Sortable columns — clicking a table header sorts by it (see index.blade.php)
        $sortColumns = [
            'invoice'  => 'invoices.invoice_number',
            'sale'     => 'sales.sale_number',
            'customer' => 'customer_sort_name',
            'due_date' => 'invoices.due_date',
            'total'    => 'invoices.grand_total',
            'paid'     => 'invoices.amount_paid',
            'balance'  => null, // computed, handled below
            'status'   => 'invoices.status',
        ];

        $sort = $request->get('sort', 'invoice');
        if (! array_key_exists($sort, $sortColumns)) {
            $sort = 'invoice';
        }

        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'balance') {
            $query->orderByRaw("(invoices.grand_total - invoices.amount_paid) {$dir}");
        } else {
            $query->orderBy($sortColumns[$sort], $dir);
        }

        // Stable tiebreaker so equal-value rows still show latest invoices first
        $query->orderByDesc('invoices.id');

        $invoices = $query->paginate(25)->withQueryString();

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
