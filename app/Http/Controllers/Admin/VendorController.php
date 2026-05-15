<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Installer;
use App\Models\Vendor;
use App\Models\VendorCreditMemo;
use App\Models\VendorRep;
use App\Services\QboSyncService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with(['creator', 'reps']);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('vendor_type', $request->type);
        }

        // Safe sorting (whitelist)
        $allowedSorts = [
            'company_name',
            'contact_name',
            'email',
            'city',
            'province',
            'vendor_type',
            'status',
            'created_at',
        ];

        $sort = $request->get('sort');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('company_name');
        }

        // Per-page
        $perPage = (int) $request->get('perPage', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

        $vendors = $query->paginate($perPage)->withQueryString();

        // Dropdown options for filters
        $statusOptions = Vendor::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $typeOptions = Vendor::query()
            ->select('vendor_type')
            ->whereNotNull('vendor_type')
            ->distinct()
            ->orderBy('vendor_type')
            ->pluck('vendor_type');

        return view('admin.vendors.index', compact('vendors', 'statusOptions', 'typeOptions'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['reps', 'creator', 'updater']);

        return view('admin.vendors.show', compact('vendor'));
    }

    // create function starts
    public function create()
    {
        $provinces = [
            '' => 'Select Province',
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NS' => 'Nova Scotia',
            'NT' => 'Northwest Territories',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon',
        ];

        $vendorTypes = [
            '' => 'Select Type',
            'Flooring Supplier' => 'Flooring Supplier',
            'Tile Distributor' => 'Tile Distributor',
            'Tools' => 'Tools',
            'Subcontractor' => 'Subcontractor',
            'Other' => 'Other',
        ];

        $reps = VendorRep::pluck('name', 'id');

        return view('admin.vendors.create', compact('provinces', 'vendorTypes', 'reps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:vendors,email',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'province' => 'nullable|string|size:2',
            'website' => 'nullable|url',
            'reps' => 'array',
            'reps.*' => 'exists:vendor_reps,id',
        ]);

        $vendor = Vendor::create($request->all());

        // Sync the selected reps (empty array if none selected)
        $vendor->reps()->sync($request->reps ?? []);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    // start edit function
    public function edit(Vendor $vendor)
    {
        $provinces = [
            '' => 'Select Province',
            'AB' => 'Alberta',
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NS' => 'Nova Scotia',
            'NT' => 'Northwest Territories',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon',
        ];

        $vendorTypes = [
            '' => 'Select Type',
            'Flooring Supplier' => 'Flooring Supplier',
            'Tile Distributor' => 'Tile Distributor',
            'Tools' => 'Tools',
            'Subcontractor' => 'Subcontractor',
            'Other' => 'Other',
        ];

        $reps = VendorRep::pluck('name', 'id');
        $selectedReps = $vendor->reps->pluck('id')->toArray();
        $installers = Installer::orderBy('contact_name')->get(['id', 'contact_name', 'vendor_id']);
        $linkedInstallerId = Installer::where('vendor_id', $vendor->id)->value('id');

        return view('admin.vendors.edit', compact('vendor', 'provinces', 'vendorTypes', 'reps', 'selectedReps', 'installers', 'linkedInstallerId'));
    }

    // start update function
    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:vendors,email,' . $vendor->id,
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'province' => 'nullable|string|size:2',
            'website' => 'nullable|url',
            'reps' => 'array',
            'reps.*' => 'exists:vendor_reps,id',
        ]);

        $vendor->update($request->all());

        if ($request->input('vendor_type') === 'Subcontractor') {
            // Clear any previously linked installer for this vendor
            Installer::where('vendor_id', $vendor->id)->update(['vendor_id' => null]);

            // Link the selected installer (if any)
            if ($request->filled('installer_id')) {
                Installer::where('id', $request->installer_id)->update(['vendor_id' => $vendor->id]);
            }

            // Clear reps for subcontractors
            $vendor->reps()->sync([]);
        } else {
            // Sync the selected reps
            $vendor->reps()->sync($request->reps ?? []);

            // Clear any installer link if type changed away from Subcontractor
            Installer::where('vendor_id', $vendor->id)->update(['vendor_id' => null]);
        }

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    // start destroy function
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function transactions(Request $request, Vendor $vendor)
    {
        $type     = $request->get('type', 'all');
        $status   = $request->get('status', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');
        $search   = $request->get('search', '');

        // Bills for this vendor
        $bills = collect();
        if ($type === 'all' || $type === 'bills') {
            $q = Bill::with(['purchaseOrder'])
                ->where('vendor_id', $vendor->id);
            if ($status && in_array($status, array_keys(Bill::STATUSES))) {
                $q->where('status', $status);
            }
            if ($dateFrom) $q->where('bill_date', '>=', $dateFrom);
            if ($dateTo)   $q->where('bill_date', '<=', $dateTo);
            if ($search)   $q->where('reference_number', 'like', "%{$search}%");
            $bills = $q->get();
        }

        // Credit memos for this vendor
        $credits = collect();
        if ($type === 'all' || $type === 'credits') {
            $q = VendorCreditMemo::with(['inventoryReturn'])
                ->where('vendor_id', $vendor->id);
            if ($status && in_array($status, ['open', 'voided'])) {
                $q->where('status', $status);
            }
            if ($dateFrom) $q->where('date', '>=', $dateFrom);
            if ($dateTo)   $q->where('date', '<=', $dateTo);
            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('credit_memo_number', 'like', "%{$search}%")
                       ->orWhere('reference_number', 'like', "%{$search}%");
                });
            }
            $credits = $q->get();
        }

        // Merge into unified transaction list
        $transactions = collect();

        foreach ($bills as $bill) {
            $transactions->push([
                'type'         => 'bill',
                'date'         => $bill->bill_date,
                'number'       => $bill->reference_number ?? '—',
                'description'  => $bill->purchaseOrder ? 'PO #'.$bill->purchaseOrder->po_number : ($bill->bill_type === 'installer' ? 'Installer bill' : 'Vendor bill'),
                'subtotal'     => (float) $bill->subtotal,
                'tax_amount'   => (float) $bill->tax_amount,
                'amount'       => (float) $bill->grand_total,
                'direction'    => 'debit',
                'status'       => $bill->status,
                'status_label' => $bill->status_label,
                'link'         => route('admin.bills.show', $bill),
                'model'        => $bill,
            ]);
        }

        foreach ($credits as $credit) {
            $transactions->push([
                'type'         => 'credit',
                'date'         => $credit->date,
                'number'       => $credit->credit_memo_number,
                'description'  => $credit->inventoryReturn
                    ? 'RTV '.$credit->inventoryReturn->return_number
                    : ($credit->reference_number ?? 'Credit memo'),
                'subtotal'     => (float) $credit->subtotal,
                'tax_amount'   => (float) $credit->tax_amount,
                'amount'       => (float) $credit->grand_total,
                'direction'    => 'credit',
                'status'       => $credit->status,
                'status_label' => $credit->status_label,
                'link'         => route('admin.vendor-credits.show', $credit),
                'model'        => $credit,
            ]);
        }

        $transactions = $transactions->sortByDesc('date')->sortByDesc(fn ($t) => $t['model']->id)->values();

        // Summary totals (unfiltered, for stat cards)
        $totalBills       = Bill::where('vendor_id', $vendor->id)->whereNotIn('status', ['voided'])->sum('grand_total');
        $outstandingBills = Bill::where('vendor_id', $vendor->id)->whereNotIn('status', ['voided', 'paid'])->sum('grand_total');
        $totalCredits     = VendorCreditMemo::where('vendor_id', $vendor->id)->where('status', 'open')->sum('grand_total');
        $netBalance       = $outstandingBills - $totalCredits;

        return view('admin.vendors.transactions', [
            'vendor'           => $vendor,
            'transactions'     => $transactions,
            'totalBills'       => $totalBills,
            'outstandingBills' => $outstandingBills,
            'totalCredits'     => $totalCredits,
            'netBalance'       => $netBalance,
            'filters'          => compact('type', 'status', 'dateFrom', 'dateTo', 'search'),
        ]);
    }

    public function pushToQbo(Vendor $vendor, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        $result = $sync->pushVendor($vendor);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }
}
