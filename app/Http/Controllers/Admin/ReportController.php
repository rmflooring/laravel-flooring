<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    // ─── Sales Report ─────────────────────────────────────────────────────────

    public function sales(Request $request)
    {
        $query = $this->buildSalesQuery($request)->orderByDesc('created_at');

        $statuses    = ['open', 'scheduled', 'in_progress', 'on_hold', 'completed', 'partially_invoiced', 'invoiced', 'cancelled'];
        $salespeople = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        if ($request->get('export') === 'csv') {
            return $this->streamCsv('sales-report', [
                'Sale #', 'Job Name', 'Customer', 'PM', 'Status', 'Grand Total', 'Invoiced Total', 'Balance', 'Created',
            ], (clone $query)->get()->map(fn($s) => [
                $s->sale_number,
                $s->job_name,
                $s->customer_name,
                $s->pm_name,
                $s->status,
                number_format($s->grand_total, 2),
                number_format($s->invoiced_total, 2),
                number_format(max(0, $s->grand_total - $s->invoiced_total), 2),
                $s->created_at->format('Y-m-d'),
            ])->toArray());
        }

        $totals = (clone $query)->toBase()
            ->select(DB::raw('COUNT(*) as total_count, SUM(grand_total) as total_value, SUM(COALESCE(invoiced_total,0)) as total_invoiced, SUM(grand_total - COALESCE(invoiced_total,0)) as total_balance'))
            ->reorder()
            ->first();

        $perPage = $this->resolvePerPage($request);
        $sales   = $query->paginate($perPage)->withQueryString();

        return view('admin.reports.sales', compact('sales', 'statuses', 'salespeople', 'totals'));
    }

    private function buildSalesQuery(Request $request)
    {
        $query = Sale::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('sale_number', 'like', "%{$s}%")
                ->orWhere('job_name', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('job_no', 'like', "%{$s}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('invoiced')) {
            if ($request->invoiced === 'uninvoiced') {
                $query->where('is_fully_invoiced', false)->whereNotIn('status', ['cancelled']);
            } elseif ($request->invoiced === 'invoiced') {
                $query->where('is_fully_invoiced', true);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_1_employee_id', $request->salesperson_id);
        }

        return $query;
    }

    // ─── AR / Invoices Report ─────────────────────────────────────────────────

    public function invoices(Request $request)
    {
        $today = now()->toDateString();

        // Aging summary across all active unpaid/partially-paid invoices (ignores form filters)
        $aging = Invoice::whereNotIn('status', ['voided', 'draft'])
            ->where('grand_total', '>', 0)
            ->selectRaw("
                SUM(grand_total - amount_paid) as total_outstanding,
                SUM(CASE WHEN (due_date IS NULL OR due_date >= ?) THEN grand_total - amount_paid ELSE 0 END) as current_amount,
                SUM(CASE WHEN due_date < ? AND DATEDIFF(?, due_date) BETWEEN 1  AND 30 THEN grand_total - amount_paid ELSE 0 END) as days_1_30,
                SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 31 AND 60 THEN grand_total - amount_paid ELSE 0 END) as days_31_60,
                SUM(CASE WHEN DATEDIFF(?, due_date) BETWEEN 61 AND 90 THEN grand_total - amount_paid ELSE 0 END) as days_61_90,
                SUM(CASE WHEN DATEDIFF(?, due_date)  > 90             THEN grand_total - amount_paid ELSE 0 END) as days_90_plus
            ", [$today, $today, $today, $today, $today, $today])
            ->first();

        $query = Invoice::with('sale')->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%{$s}%")
                ->orWhereHas('sale', fn($sq) => $sq
                    ->where('job_name', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('sale_number', 'like', "%{$s}%")
                )
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->boolean('overdue_only')) {
            $query->where('due_date', '<', $today)->whereNotIn('status', ['paid', 'voided', 'draft']);
        }

        $statuses = ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'voided'];

        if ($request->get('export') === 'csv') {
            return $this->streamCsv('invoices-report', [
                'Invoice #', 'Sale #', 'Customer', 'Job', 'Invoice Date', 'Due Date', 'Status', 'Total', 'Paid', 'Balance',
            ], (clone $query)->get()->map(fn($inv) => [
                $inv->invoice_number,
                $inv->sale?->sale_number ?? '',
                $inv->sale?->customer_name ?? '',
                $inv->sale?->job_name ?? '',
                $inv->created_at->format('Y-m-d'),
                $inv->due_date?->format('Y-m-d') ?? '',
                $inv->status,
                number_format($inv->grand_total, 2),
                number_format($inv->amount_paid, 2),
                number_format($inv->balance_due, 2),
            ])->toArray());
        }

        $perPage  = $this->resolvePerPage($request);
        $invoices = $query->paginate($perPage)->withQueryString();

        return view('admin.reports.invoices', compact('invoices', 'statuses', 'aging'));
    }

    // ─── Revenue Summary ──────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $availableYears = Invoice::whereNotIn('status', ['voided', 'draft'])
            ->selectRaw('YEAR(created_at) as yr')
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('yr');

        $year = (int) $request->get('year', $availableYears->first() ?? now()->year);

        $monthly = Invoice::whereNotIn('status', ['voided', 'draft'])
            ->whereYear('created_at', $year)
            ->selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as month_key,
                DATE_FORMAT(created_at, '%M %Y') as month_label,
                COUNT(*) as invoice_count,
                SUM(grand_total) as total_invoiced,
                SUM(amount_paid) as total_paid,
                SUM(grand_total - amount_paid) as outstanding
            ")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%M %Y')")
            ->orderBy('month_key')
            ->get();

        $yearTotals = [
            'invoice_count'  => $monthly->sum('invoice_count'),
            'total_invoiced' => $monthly->sum('total_invoiced'),
            'total_paid'     => $monthly->sum('total_paid'),
            'outstanding'    => $monthly->sum('outstanding'),
        ];

        if ($request->get('export') === 'csv') {
            return $this->streamCsv("revenue-{$year}", [
                'Month', '# Invoices', 'Total Invoiced', 'Total Paid', 'Outstanding',
            ], $monthly->map(fn($row) => [
                $row->month_label,
                $row->invoice_count,
                number_format($row->total_invoiced, 2),
                number_format($row->total_paid, 2),
                number_format($row->outstanding, 2),
            ])->toArray());
        }

        return view('admin.reports.revenue', compact('monthly', 'year', 'availableYears', 'yearTotals'));
    }

    // ─── Purchase Orders Report ───────────────────────────────────────────────

    public function purchaseOrders(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'sale'])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('po_number', 'like', "%{$s}%")
                ->orWhereHas('vendor', fn($vq) => $vq->where('name', 'like', "%{$s}%"))
                ->orWhereHas('sale', fn($sq) => $sq
                    ->where('job_name', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                )
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $statuses = ['pending', 'ordered', 'received', 'delivered', 'cancelled'];
        $vendors  = Vendor::orderBy('name')->get(['id', 'name']);

        if ($request->get('export') === 'csv') {
            return $this->streamCsv('purchase-orders-report', [
                'PO #', 'Sale #', 'Customer', 'Vendor', 'Status', 'Expected Delivery', 'Created',
            ], (clone $query)->get()->map(fn($po) => [
                $po->po_number,
                $po->sale?->sale_number ?? '',
                $po->sale?->customer_name ?? '',
                $po->vendor?->name ?? '',
                $po->status,
                $po->expected_delivery_date?->format('Y-m-d') ?? '',
                $po->created_at->format('Y-m-d'),
            ])->toArray());
        }

        $perPage        = $this->resolvePerPage($request);
        $purchaseOrders = $query->paginate($perPage)->withQueryString();

        return view('admin.reports.purchase_orders', compact('purchaseOrders', 'statuses', 'vendors'));
    }

    // ─── Aging Estimates Report ───────────────────────────────────────────────

    public function agingEstimates(Request $request)
    {
        $query = Estimate::whereNotNull('first_sent_at')
            ->whereDoesntHave('sale')
            ->with(['followUps' => fn($q) => $q->latest(), 'creator', 'opportunity.jobSiteCustomer', 'salesperson1Employee'])
            ->orderByDesc('first_sent_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('estimate_number', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('homeowner_name', 'like', "%{$s}%")
                ->orWhere('job_name', 'like', "%{$s}%")
                ->orWhere('job_no', 'like', "%{$s}%")
            );
        }

        if ($request->filled('stage')) {
            $query->where('follow_up_stage', $request->stage);
        }

        if ($request->boolean('show_closed')) {
            $query->where('follow_up_closed', true);
        } else {
            $query->where('follow_up_closed', false);
        }

        if ($request->filled('estimator_id')) {
            $query->where('created_by', $request->estimator_id);
        }

        $perPage   = $this->resolvePerPage($request);
        $estimates = $query->paginate($perPage)->withQueryString();
        $estimators = \App\Models\User::orderBy('name')->get(['id', 'name']);

        // Separate "action required" set — due for a follow-up right now, not closed
        $actionRequired = Estimate::whereNotNull('first_sent_at')
            ->whereDoesntHave('sale')
            ->where('follow_up_closed', false)
            ->where(function ($q) {
                $q->where(fn($q) => $q->where('follow_up_stage', 0)->where('first_sent_at', '<=', now()->subDays(7)))
                  ->orWhere(fn($q) => $q->where('follow_up_stage', 1)->where('first_sent_at', '<=', now()->subDays(14)))
                  ->orWhere(fn($q) => $q->where('follow_up_stage', 2)->where('first_sent_at', '<=', now()->subDays(30)));
            })
            ->with(['followUps' => fn($q) => $q->latest(), 'creator', 'opportunity.jobSiteCustomer'])
            ->orderBy('first_sent_at')
            ->get();

        return view('admin.reports.aging_estimates', compact('estimates', 'estimators', 'actionRequired'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolvePerPage(Request $request, int $default = 25): int
    {
        $perPage = (int) $request->get('perPage', $default);
        return in_array($perPage, [25, 50, 100], true) ? $perPage : $default;
    }

    private function streamCsv(string $filename, array $headers, array $rows)
    {
        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
