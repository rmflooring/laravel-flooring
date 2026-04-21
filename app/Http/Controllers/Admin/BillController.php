<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Installer;
use App\Models\PaymentTerm;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Services\QboSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    // -----------------------------------------------------------------------
    // INDEX — payables dashboard
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = Bill::with(['vendor', 'installer', 'purchaseOrder', 'workOrder'])
            ->whereNotIn('status', ['voided']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('bill_type')) {
            $query->where('bill_type', $request->bill_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('company_name', 'like', "%{$search}%"))
                  ->orWhereHas('installer', fn ($i) => $i->where('company_name', 'like', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn ($p) => $p->where('po_number', 'like', "%{$search}%"))
                  ->orWhereHas('workOrder', fn ($w) => $w->where('wo_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('due_from')) {
            $query->where('due_date', '>=', $request->due_from);
        }

        if ($request->filled('due_to')) {
            $query->where('due_date', '<=', $request->due_to);
        }

        $bills = $query->orderBy('due_date')->orderBy('id', 'desc')->paginate(25)->withQueryString();

        // Stat cards (active bills only, excluding voided)
        $activeQuery = Bill::whereNotIn('status', ['voided', 'approved']);

        $totalOutstanding = (clone $activeQuery)->sum('grand_total');
        $totalOverdue     = Bill::where('status', 'overdue')->sum('grand_total');
        $dueThisWeek      = Bill::whereNotIn('status', ['voided', 'approved', 'overdue'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('grand_total');

        return view('admin.bills.index', [
            'bills'            => $bills,
            'statuses'         => Bill::STATUSES,
            'totalOutstanding' => $totalOutstanding,
            'totalOverdue'     => $totalOverdue,
            'dueThisWeek'      => $dueThisWeek,
            'filters'          => $request->only('status', 'bill_type', 'search', 'due_from', 'due_to'),
        ]);
    }

    // -----------------------------------------------------------------------
    // CREATE
    // -----------------------------------------------------------------------

    public function create(Request $request)
    {
        $purchaseOrder = null;
        $workOrder     = null;
        $billType      = 'vendor';

        if ($request->filled('purchase_order')) {
            $purchaseOrder = PurchaseOrder::with(['vendor', 'items', 'sale'])->findOrFail($request->purchase_order);
            $billType = 'vendor';
        } elseif ($request->filled('work_order')) {
            $workOrder = WorkOrder::with(['installer', 'items', 'sale'])->findOrFail($request->work_order);
            $billType = 'installer';
        }

        $paymentTerms = PaymentTerm::where('is_active', true)->orderBy('name')->get();
        $vendors      = Vendor::where('status', 'active')->orderBy('company_name')->get();
        $installers   = Installer::where('status', 'active')->orderBy('company_name')->get();

        return view('admin.bills.create', [
            'billType'      => $billType,
            'purchaseOrder' => $purchaseOrder,
            'workOrder'     => $workOrder,
            'paymentTerms'  => $paymentTerms,
            'vendors'       => $vendors,
            'installers'    => $installers,
        ]);
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_type'           => 'required|in:vendor,installer',
            'vendor_id'           => 'nullable|exists:vendors,id',
            'installer_id'        => 'nullable|exists:installers,id',
            'purchase_order_id'   => 'nullable|exists:purchase_orders,id',
            'work_order_id'       => 'nullable|exists:work_orders,id',
            'payment_term_id'     => 'nullable|exists:payment_terms,id',
            'reference_number'    => 'required|string|max:100',
            'bill_date'           => 'required|date',
            'due_date'            => 'nullable|date|after_or_equal:bill_date',
            'gst_rate'            => 'required|numeric|min:0|max:100',
            'pst_rate'            => 'required|numeric|min:0|max:100',
            'tax_manual'          => 'boolean',
            'gst_amount_override' => 'nullable|numeric|min:0',
            'pst_amount_override' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.item_name'              => 'required|string|max:255',
            'items.*.quantity'               => 'required|numeric|min:0',
            'items.*.unit'                   => 'nullable|string|max:20',
            'items.*.unit_cost'              => 'required|numeric|min:0',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.work_order_item_id'     => 'nullable|exists:work_order_items,id',
        ]);

        // If due date not provided but payment term has days, compute it
        if (empty($validated['due_date']) && ! empty($validated['payment_term_id'])) {
            $term = PaymentTerm::find($validated['payment_term_id']);
            if ($term && $term->days !== null) {
                $validated['due_date'] = \Carbon\Carbon::parse($validated['bill_date'])
                    ->addDays($term->days)
                    ->toDateString();
            }
        }

        // Convert percentage inputs to decimal rates
        $gstRate   = round(($validated['gst_rate'] ?? 0) / 100, 6);
        $pstRate   = round(($validated['pst_rate'] ?? 0) / 100, 6);
        $taxManual = $request->boolean('tax_manual');

        $bill = DB::transaction(function () use ($validated, $gstRate, $pstRate, $taxManual) {
            $bill = Bill::create([
                'bill_type'         => $validated['bill_type'],
                'vendor_id'         => $validated['vendor_id'] ?? null,
                'installer_id'      => $validated['installer_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'work_order_id'     => $validated['work_order_id'] ?? null,
                'payment_term_id'   => $validated['payment_term_id'] ?? null,
                'reference_number'  => $validated['reference_number'],
                'bill_date'         => $validated['bill_date'],
                'due_date'          => $validated['due_date'] ?? null,
                'gst_rate'          => $gstRate,
                'pst_rate'          => $pstRate,
                'tax_manual'        => $taxManual,
                'status'            => 'pending',
                'notes'             => $validated['notes'] ?? null,
                'subtotal'          => 0,
                'gst_amount'        => $taxManual ? round($validated['gst_amount_override'] ?? 0, 2) : 0,
                'pst_amount'        => $taxManual ? round($validated['pst_amount_override'] ?? 0, 2) : 0,
                'tax_amount'        => 0,
                'grand_total'       => 0,
            ]);

            foreach ($validated['items'] as $i => $row) {
                BillItem::create([
                    'bill_id'                 => $bill->id,
                    'purchase_order_item_id'  => $row['purchase_order_item_id'] ?? null,
                    'work_order_item_id'      => $row['work_order_item_id'] ?? null,
                    'item_name'               => $row['item_name'],
                    'quantity'                => $row['quantity'],
                    'unit'                    => $row['unit'] ?? null,
                    'unit_cost'               => $row['unit_cost'],
                    'line_total'              => round($row['quantity'] * $row['unit_cost'], 2),
                    'sort_order'              => $i,
                ]);
            }

            $bill->load('items');
            $bill->recalculateTotals();

            return $bill;
        });

        return redirect()->route('admin.bills.show', $bill)
            ->with('success', 'Bill created successfully.');
    }

    // -----------------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------------

    public function show(Bill $bill)
    {
        $bill->load(['vendor', 'installer', 'purchaseOrder', 'workOrder', 'items', 'paymentTerm', 'creator', 'updater']);

        return view('admin.bills.show', compact('bill'));
    }

    // -----------------------------------------------------------------------
    // EDIT
    // -----------------------------------------------------------------------

    public function edit(Bill $bill)
    {
        if ($bill->status === 'voided') {
            return redirect()->route('admin.bills.show', $bill)->with('error', 'Voided bills cannot be edited.');
        }

        $bill->load(['vendor', 'installer', 'purchaseOrder', 'workOrder', 'items', 'paymentTerm']);

        $paymentTerms = PaymentTerm::where('is_active', true)->orderBy('name')->get();
        $vendors      = Vendor::where('status', 'active')->orderBy('company_name')->get();
        $installers   = Installer::where('status', 'active')->orderBy('company_name')->get();

        return view('admin.bills.edit', [
            'bill'         => $bill,
            'paymentTerms' => $paymentTerms,
            'vendors'      => $vendors,
            'installers'   => $installers,
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function update(Request $request, Bill $bill)
    {
        if ($bill->status === 'voided') {
            return redirect()->route('admin.bills.show', $bill)->with('error', 'Voided bills cannot be edited.');
        }

        $validated = $request->validate([
            'vendor_id'           => 'nullable|exists:vendors,id',
            'installer_id'        => 'nullable|exists:installers,id',
            'payment_term_id'     => 'nullable|exists:payment_terms,id',
            'reference_number'    => 'required|string|max:100',
            'bill_date'           => 'required|date',
            'due_date'            => 'nullable|date|after_or_equal:bill_date',
            'status'              => 'required|in:draft,pending,approved,overdue',
            'gst_rate'            => 'required|numeric|min:0|max:100',
            'pst_rate'            => 'required|numeric|min:0|max:100',
            'tax_manual'          => 'boolean',
            'gst_amount_override' => 'nullable|numeric|min:0',
            'pst_amount_override' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.id'                     => 'nullable|exists:bill_items,id',
            'items.*.item_name'              => 'required|string|max:255',
            'items.*.quantity'               => 'required|numeric|min:0',
            'items.*.unit'                   => 'nullable|string|max:20',
            'items.*.unit_cost'              => 'required|numeric|min:0',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.work_order_item_id'     => 'nullable|exists:work_order_items,id',
        ]);

        $gstRate   = round(($validated['gst_rate'] ?? 0) / 100, 6);
        $pstRate   = round(($validated['pst_rate'] ?? 0) / 100, 6);
        $taxManual = $request->boolean('tax_manual');

        DB::transaction(function () use ($bill, $validated, $gstRate, $pstRate, $taxManual) {
            $bill->update([
                'vendor_id'        => $validated['vendor_id'] ?? null,
                'installer_id'     => $validated['installer_id'] ?? null,
                'payment_term_id'  => $validated['payment_term_id'] ?? null,
                'reference_number' => $validated['reference_number'],
                'bill_date'        => $validated['bill_date'],
                'due_date'         => $validated['due_date'] ?? null,
                'status'           => $validated['status'],
                'gst_rate'         => $gstRate,
                'pst_rate'         => $pstRate,
                'tax_manual'       => $taxManual,
                'gst_amount'       => $taxManual ? round($validated['gst_amount_override'] ?? 0, 2) : 0,
                'pst_amount'       => $taxManual ? round($validated['pst_amount_override'] ?? 0, 2) : 0,
                'notes'            => $validated['notes'] ?? null,
            ]);

            // Delete all existing items and recreate
            $bill->items()->delete();

            foreach ($validated['items'] as $i => $row) {
                BillItem::create([
                    'bill_id'                => $bill->id,
                    'purchase_order_item_id' => $row['purchase_order_item_id'] ?? null,
                    'work_order_item_id'     => $row['work_order_item_id'] ?? null,
                    'item_name'              => $row['item_name'],
                    'quantity'               => $row['quantity'],
                    'unit'                   => $row['unit'] ?? null,
                    'unit_cost'              => $row['unit_cost'],
                    'line_total'             => round($row['quantity'] * $row['unit_cost'], 2),
                    'sort_order'             => $i,
                ]);
            }

            $bill->load('items');
            $bill->recalculateTotals();
        });

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill updated.');
    }

    // -----------------------------------------------------------------------
    // VOID
    // -----------------------------------------------------------------------

    public function void(Request $request, Bill $bill)
    {
        if ($bill->status === 'voided') {
            return redirect()->route('admin.bills.show', $bill)->with('error', 'Bill is already voided.');
        }

        $request->validate([
            'void_reason' => 'nullable|string|max:500',
        ]);

        $bill->update([
            'status'     => 'voided',
            'voided_at'  => now(),
            'void_reason' => $request->void_reason,
        ]);

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill voided.');
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function destroy(Bill $bill)
    {
        $bill->delete();

        return redirect()->route('admin.bills.index')->with('success', "Bill #{$bill->reference_number} deleted.");
    }

    public function pushToQbo(Bill $bill, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        $apAccountId = Setting::get('qbo_ap_account_id');
        if (! $apAccountId) {
            return back()->with('error', 'No QBO expense account configured. Visit Settings → QuickBooks Online to set it up.');
        }

        $result = $sync->pushBill($bill, $apAccountId);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    // -----------------------------------------------------------------------
    // AGING REPORT
    // -----------------------------------------------------------------------

    public function aging(Request $request)
    {
        $billType = $request->get('bill_type', 'all');

        $query = Bill::with(['vendor', 'installer'])
            ->whereIn('status', ['pending', 'approved', 'overdue']);

        if ($billType !== 'all') {
            $query->where('bill_type', $billType);
        }

        $bills = $query->get();

        // Group by payee and bucket
        $aging = [];
        $buckets = ['current', '1_30', '31_60', '61_90', '90_plus'];
        $bucketLabels = [
            'current' => 'Current',
            '1_30'    => '1–30 days',
            '31_60'   => '31–60 days',
            '61_90'   => '61–90 days',
            '90_plus' => '90+ days',
        ];

        foreach ($bills as $bill) {
            $key  = $bill->bill_type . '_' . ($bill->bill_type === 'vendor' ? $bill->vendor_id : $bill->installer_id);
            $name = $bill->payee_name;

            if (! isset($aging[$key])) {
                $aging[$key] = [
                    'name'     => $name,
                    'type'     => $bill->bill_type,
                    'current'  => 0,
                    '1_30'     => 0,
                    '31_60'    => 0,
                    '61_90'    => 0,
                    '90_plus'  => 0,
                    'total'    => 0,
                ];
            }

            $bucket = $bill->aging_bucket;
            $aging[$key][$bucket] += $bill->grand_total;
            $aging[$key]['total'] += $bill->grand_total;
        }

        // Sort by total desc
        usort($aging, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Column totals
        $totals = array_fill_keys(array_merge($buckets, ['total']), 0);
        foreach ($aging as $row) {
            foreach (array_merge($buckets, ['total']) as $col) {
                $totals[$col] += $row[$col];
            }
        }

        return view('admin.bills.aging', [
            'aging'        => $aging,
            'totals'       => $totals,
            'buckets'      => $buckets,
            'bucketLabels' => $bucketLabels,
            'billType'     => $billType,
        ]);
    }
}
