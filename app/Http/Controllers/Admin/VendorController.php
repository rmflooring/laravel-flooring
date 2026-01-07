<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorRep;
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

        return view('admin.vendors.edit', compact('vendor', 'provinces', 'vendorTypes', 'reps', 'selectedReps'));
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

        // Sync the selected reps
        $vendor->reps()->sync($request->reps ?? []);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    // start destroy function
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
