<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorRep;
use Illuminate\Http\Request;

class VendorRepController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorRep::with(['creator', 'vendors']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $sortable = ['name', 'email'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'name';
        $dir  = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        $perPage = in_array((int) $request->input('perPage'), [15, 25, 50, 100])
            ? (int) $request->input('perPage')
            : 15;

        $reps = $query->paginate($perPage)->withQueryString();

        return view('admin.vendor_reps.index', compact('reps'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);

        return view('admin.vendor_reps.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'vendor_id' => 'nullable|exists:vendors,id',
            'phone'     => 'nullable|string',
            'mobile'    => 'nullable|string',
            'email'     => 'nullable|email',
            'notes'     => 'nullable|string',
        ]);

        $rep = VendorRep::create($request->only('name', 'phone', 'mobile', 'email', 'notes'));

        if ($request->filled('vendor_id')) {
            $rep->vendors()->sync([$request->vendor_id]);
        }

        return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep created successfully.');
    }

    public function show(VendorRep $vendorRep)
    {
        $vendorRep->load(['vendors', 'creator', 'updater']);

        return view('admin.vendor_reps.show', compact('vendorRep'));
    }

    public function edit(VendorRep $vendorRep)
    {
        $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);
        $currentVendorId = $vendorRep->vendors->first()?->id;

        return view('admin.vendor_reps.edit', compact('vendorRep', 'vendors', 'currentVendorId'));
    }

    public function update(Request $request, VendorRep $vendorRep)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'vendor_id' => 'nullable|exists:vendors,id',
            'phone'     => 'nullable|string',
            'mobile'    => 'nullable|string',
            'email'     => 'nullable|email',
            'notes'     => 'nullable|string',
        ]);

        $vendorRep->update($request->only('name', 'phone', 'mobile', 'email', 'notes'));

        $vendorRep->vendors()->sync($request->filled('vendor_id') ? [$request->vendor_id] : []);

        return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep updated successfully.');
    }

    public function destroy(VendorRep $vendorRep)
    {
        $vendorRep->delete();

        return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep deleted successfully.');
    }
}
