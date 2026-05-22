<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryReturn;
use App\Models\Vendor;
use App\Models\VendorCreditMemo;
use App\Services\QboSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class VendorCreditMemoController extends Controller
{
    // -----------------------------------------------------------------------
    // INDEX
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $query = VendorCreditMemo::with('vendor')
            ->where('status', '<>', 'voided');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('status')) {
            $query->withTrashed()->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('credit_memo_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('company_name', 'like', "%{$search}%"));
            });
        }

        $credits = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $totalOpen = VendorCreditMemo::where('status', 'open')->sum('grand_total');

        $vendors = Vendor::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.vendor-credits.index', [
            'credits'   => $credits,
            'totalOpen' => $totalOpen,
            'vendors'   => $vendors,
            'filters'   => $request->only('vendor_id', 'status', 'search'),
        ]);
    }

    // -----------------------------------------------------------------------
    // CREATE
    // -----------------------------------------------------------------------

    public function create(Request $request)
    {
        $rtv = null;
        if ($request->filled('rtv')) {
            $rtv = InventoryReturn::with(['vendor', 'items'])->findOrFail($request->rtv);
        }

        $vendors = Vendor::where('status', 'active')->orderBy('company_name')->get();

        return view('admin.vendor-credits.create', [
            'rtv'       => $rtv,
            'vendors'   => $vendors,
            'taxGroups' => $this->taxGroups(),
        ]);
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'inventory_return_id' => 'nullable|exists:inventory_returns,id',
            'reference_number'    => 'nullable|string|max:100',
            'date'                => 'required|date',
            'subtotal'            => 'required|numeric|min:0',
            'gst_rate'            => 'required|numeric|min:0|max:100',
            'pst_rate'            => 'required|numeric|min:0|max:100',
            'tax_manual'          => 'boolean',
            'gst_amount_override' => 'nullable|numeric|min:0',
            'pst_amount_override' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $gstRate   = round(($validated['gst_rate'] ?? 0) / 100, 6);
        $pstRate   = round(($validated['pst_rate'] ?? 0) / 100, 6);
        $taxManual = $request->boolean('tax_manual');
        $subtotal  = round((float) $validated['subtotal'], 2);

        $gst = $taxManual
            ? round((float) ($validated['gst_amount_override'] ?? 0), 2)
            : round($subtotal * $gstRate, 2);
        $pst = $taxManual
            ? round((float) ($validated['pst_amount_override'] ?? 0), 2)
            : round($subtotal * $pstRate, 2);

        $vcm = VendorCreditMemo::create([
            'vendor_id'           => $validated['vendor_id'],
            'inventory_return_id' => $validated['inventory_return_id'] ?? null,
            'reference_number'    => $validated['reference_number'] ?? null,
            'date'                => $validated['date'],
            'subtotal'            => $subtotal,
            'gst_rate'            => $gstRate,
            'pst_rate'            => $pstRate,
            'tax_manual'          => $taxManual,
            'gst_amount'          => $gst,
            'pst_amount'          => $pst,
            'tax_amount'          => round($gst + $pst, 2),
            'grand_total'         => round($subtotal + $gst + $pst, 2),
            'status'              => 'open',
            'notes'               => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.vendor-credits.show', $vcm)
            ->with('success', "Credit memo {$vcm->credit_memo_number} created.");
    }

    // -----------------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------------

    public function show(VendorCreditMemo $vendorCredit)
    {
        $vendorCredit->load(['vendor', 'inventoryReturn', 'creator', 'updater']);

        return view('admin.vendor-credits.show', compact('vendorCredit'));
    }

    // -----------------------------------------------------------------------
    // EDIT
    // -----------------------------------------------------------------------

    public function edit(VendorCreditMemo $vendorCredit)
    {
        if ($vendorCredit->status === 'voided') {
            return redirect()->route('admin.vendor-credits.show', $vendorCredit)
                ->with('error', 'Voided credit memos cannot be edited.');
        }

        $vendorCredit->load(['vendor', 'inventoryReturn']);

        $vendors = Vendor::where('status', 'active')->orderBy('company_name')->get();

        return view('admin.vendor-credits.edit', [
            'vendorCredit' => $vendorCredit,
            'vendors'      => $vendors,
            'taxGroups'    => $this->taxGroups(),
        ]);
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function update(Request $request, VendorCreditMemo $vendorCredit)
    {
        if ($vendorCredit->status === 'voided') {
            return redirect()->route('admin.vendor-credits.show', $vendorCredit)
                ->with('error', 'Voided credit memos cannot be edited.');
        }

        $validated = $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'reference_number'    => 'nullable|string|max:100',
            'date'                => 'required|date',
            'subtotal'            => 'required|numeric|min:0',
            'gst_rate'            => 'required|numeric|min:0|max:100',
            'pst_rate'            => 'required|numeric|min:0|max:100',
            'tax_manual'          => 'boolean',
            'gst_amount_override' => 'nullable|numeric|min:0',
            'pst_amount_override' => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $gstRate   = round(($validated['gst_rate'] ?? 0) / 100, 6);
        $pstRate   = round(($validated['pst_rate'] ?? 0) / 100, 6);
        $taxManual = $request->boolean('tax_manual');
        $subtotal  = round((float) $validated['subtotal'], 2);

        $gst = $taxManual
            ? round((float) ($validated['gst_amount_override'] ?? 0), 2)
            : round($subtotal * $gstRate, 2);
        $pst = $taxManual
            ? round((float) ($validated['pst_amount_override'] ?? 0), 2)
            : round($subtotal * $pstRate, 2);

        $vendorCredit->update([
            'vendor_id'        => $validated['vendor_id'],
            'reference_number' => $validated['reference_number'] ?? null,
            'date'             => $validated['date'],
            'subtotal'         => $subtotal,
            'gst_rate'         => $gstRate,
            'pst_rate'         => $pstRate,
            'tax_manual'       => $taxManual,
            'gst_amount'       => $gst,
            'pst_amount'       => $pst,
            'tax_amount'       => round($gst + $pst, 2),
            'grand_total'      => round($subtotal + $gst + $pst, 2),
            'notes'            => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.vendor-credits.show', $vendorCredit)
            ->with('success', 'Credit memo updated.');
    }

    // -----------------------------------------------------------------------
    // VOID
    // -----------------------------------------------------------------------

    public function void(Request $request, VendorCreditMemo $vendorCredit)
    {
        if ($vendorCredit->status === 'voided') {
            return redirect()->route('admin.vendor-credits.show', $vendorCredit)
                ->with('error', 'Credit memo is already voided.');
        }

        $request->validate([
            'void_reason' => 'nullable|string|max:500',
        ]);

        $vendorCredit->update([
            'status'      => 'voided',
            'voided_at'   => now(),
            'void_reason' => $request->void_reason,
        ]);

        return redirect()->route('admin.vendor-credits.show', $vendorCredit)
            ->with('success', 'Credit memo voided.');
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function destroy(VendorCreditMemo $vendorCredit)
    {
        $vendorCredit->delete();

        return redirect()->route('admin.vendor-credits.index')
            ->with('success', "Credit memo {$vendorCredit->credit_memo_number} deleted.");
    }

    // -----------------------------------------------------------------------
    // MARK APPLIED
    // -----------------------------------------------------------------------

    public function markApplied(VendorCreditMemo $vendorCredit)
    {
        if ($vendorCredit->status !== 'open') {
            return back()->with('error', 'Only open credit memos can be marked as applied.');
        }

        $vendorCredit->update(['status' => 'applied']);

        return back()->with('success', 'Credit memo marked as applied.');
    }

    // -----------------------------------------------------------------------
    // QBO PUSH
    // -----------------------------------------------------------------------

    public function pushToQbo(VendorCreditMemo $vendorCredit, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        if ($vendorCredit->status === 'voided') {
            return back()->with('error', 'Voided credit memos cannot be pushed to QuickBooks.');
        }

        $accountIds = [
            'product'     => Setting::get('qbo_ap_product_account_id'),
            'freight'     => Setting::get('qbo_ap_freight_account_id'),
            'labour'      => Setting::get('qbo_ap_labour_account_id'),
            'gst_rate_id' => Setting::get('qbo_ap_gst_tax_rate_id'),
            'pst_rate_id' => Setting::get('qbo_ap_pst_tax_rate_id'),
        ];

        if (! $accountIds['product']) {
            return back()->with('error', 'Missing QBO expense account. Visit Settings → QuickBooks Online to set it up.');
        }

        $result = $sync->pushVendorCredit($vendorCredit, $accountIds);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    // -----------------------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------------------

    private function taxGroups(): \Illuminate\Support\Collection
    {
        return DB::table('tax_rate_groups as g')
            ->select('g.id', 'g.name', 'g.description')
            ->selectRaw('SUM(CASE WHEN tr.name = "GST" THEN tr.purchase_rate ELSE 0 END) as gst_rate')
            ->selectRaw('SUM(CASE WHEN tr.name = "PST" THEN tr.purchase_rate ELSE 0 END) as pst_rate')
            ->join('tax_rate_group_items as i', 'g.id', '=', 'i.tax_rate_group_id')
            ->join('tax_rates as tr', 'i.tax_rate_id', '=', 'tr.id')
            ->whereNull('g.deleted_at')
            ->groupBy('g.id', 'g.name', 'g.description')
            ->orderBy('g.name')
            ->get();
    }
}
