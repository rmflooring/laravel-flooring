<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GLAccount;
use App\Models\Installer;
use App\Models\Vendor;
use Illuminate\Http\Request;

class InstallerController extends Controller
{
    private array $provinces = [
        ''   => 'Select Province',
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

    public function index(Request $request)
    {
        $query = Installer::with(['vendor', 'glCostAccount', 'glSaleAccount', 'creator']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $allowedSorts = ['company_name', 'contact_name', 'city', 'province', 'status', 'created_at'];
        $sort = $request->get('sort');
        $dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('company_name');
        }

        $perPage = (int) $request->get('perPage', 15);
        $perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

        $installers = $query->paginate($perPage)->withQueryString();

        return view('admin.installers.index', compact('installers'));
    }

    public function create()
    {
        $subcontractors = Vendor::where('vendor_type', 'Subcontractor')
            ->where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_name', 'phone', 'mobile', 'email',
                   'address', 'address2', 'city', 'province', 'postal_code',
                   'account_number', 'terms']);

        $glAccounts = GLAccount::where('status', 'active')
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'name']);

        return view('admin.installers.create', [
            'subcontractors' => $subcontractors,
            'glAccounts'     => $glAccounts,
            'provinces'      => $this->provinces,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name'       => 'required|string|max:255',
            'contact_name'       => 'nullable|string|max:255',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:50',
            'mobile'             => 'nullable|string|max:50',
            'address'            => 'nullable|string|max:255',
            'address2'           => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'province'           => 'nullable|string|size:2',
            'postal_code'        => 'nullable|string|max:10',
            'account_number'     => 'nullable|string|max:100',
            'gst_number'         => 'nullable|string|max:100',
            'terms'              => 'nullable|string|max:100',
            'gl_cost_account_id' => 'nullable|exists:gl_accounts,id',
            'gl_sale_account_id' => 'nullable|exists:gl_accounts,id',
            'status'             => 'required|in:active,inactive',
            'notes'              => 'nullable|string',
            'vendor_id'          => 'nullable|exists:vendors,id',
        ]);

        Installer::create($request->only([
            'vendor_id', 'company_name', 'contact_name', 'phone', 'mobile', 'email',
            'address', 'address2', 'city', 'province', 'postal_code',
            'account_number', 'gst_number', 'terms',
            'gl_cost_account_id', 'gl_sale_account_id',
            'status', 'notes',
        ]));

        return redirect()->route('admin.installers.index')
            ->with('success', 'Installer created successfully.');
    }

    public function show(Installer $installer)
    {
        $installer->load(['vendor', 'glCostAccount', 'glSaleAccount', 'creator', 'updater']);

        return view('admin.installers.show', [
            'installer' => $installer,
            'provinces' => $this->provinces,
        ]);
    }

    public function edit(Installer $installer)
    {
        $subcontractors = Vendor::where('vendor_type', 'Subcontractor')
            ->where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_name', 'phone', 'mobile', 'email',
                   'address', 'address2', 'city', 'province', 'postal_code',
                   'account_number', 'terms']);

        $glAccounts = GLAccount::where('status', 'active')
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'name']);

        return view('admin.installers.edit', [
            'installer'      => $installer,
            'subcontractors' => $subcontractors,
            'glAccounts'     => $glAccounts,
            'provinces'      => $this->provinces,
        ]);
    }

    public function update(Request $request, Installer $installer)
    {
        $request->validate([
            'company_name'       => 'required|string|max:255',
            'contact_name'       => 'nullable|string|max:255',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:50',
            'mobile'             => 'nullable|string|max:50',
            'address'            => 'nullable|string|max:255',
            'address2'           => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'province'           => 'nullable|string|size:2',
            'postal_code'        => 'nullable|string|max:10',
            'account_number'     => 'nullable|string|max:100',
            'gst_number'         => 'nullable|string|max:100',
            'terms'              => 'nullable|string|max:100',
            'gl_cost_account_id' => 'nullable|exists:gl_accounts,id',
            'gl_sale_account_id' => 'nullable|exists:gl_accounts,id',
            'status'             => 'required|in:active,inactive',
            'notes'              => 'nullable|string',
            'vendor_id'          => 'nullable|exists:vendors,id',
        ]);

        $installer->update($request->only([
            'vendor_id', 'company_name', 'contact_name', 'phone', 'mobile', 'email',
            'address', 'address2', 'city', 'province', 'postal_code',
            'account_number', 'gst_number', 'terms',
            'gl_cost_account_id', 'gl_sale_account_id',
            'status', 'notes',
        ]));

        return redirect()->route('admin.installers.show', $installer)
            ->with('success', 'Installer updated successfully.');
    }

    public function destroy(Installer $installer)
    {
        $installer->delete();

        return redirect()->route('admin.installers.index')
            ->with('success', 'Installer deleted successfully.');
    }
}
