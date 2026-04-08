<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sample;
use App\Models\SampleCheckout;
use App\Models\SampleSet;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    // -----------------------------------------------------------------------
    // SHOW — product info page (scanned from QR)
    // -----------------------------------------------------------------------

    public function show(string $sampleId)
    {
        // Support both SMP-xxxx (individual) and SET-xxxx (set) IDs
        if (str_starts_with($sampleId, 'SET-')) {
            $sampleSet = SampleSet::where('set_id', $sampleId)
                ->with(['productLine', 'items.productStyle', 'activeCheckout'])
                ->firstOrFail();

            return view('mobile.samples.show-set', compact('sampleSet'));
        }

        $sample = Sample::where('sample_id', $sampleId)
            ->with([
                'productStyle.productLine',
                'productStyle.photos',
                'activeCheckouts',
            ])
            ->firstOrFail();

        return view('mobile.samples.show', compact('sample'));
    }

    // -----------------------------------------------------------------------
    // CHECKOUT FORM
    // -----------------------------------------------------------------------

    public function checkout(string $sampleId)
    {
        $sample = Sample::where('sample_id', $sampleId)
            ->with(['productStyle.productLine'])
            ->firstOrFail();

        if ($sample->available_qty <= 0) {
            return redirect()->route('mobile.samples.show', $sampleId)
                ->with('error', 'No copies of this sample are currently available for checkout.');
        }

        $customers = Customer::orderBy('company_name')->get(['id', 'company_name', 'name', 'phone', 'email']);
        $staffUsers = User::whereHas('roles', fn ($q) => $q->whereNotIn('name', ['installer']))
            ->orderBy('name')->get(['id', 'name']);

        $defaultDays = (int) Setting::get('sample_checkout_days', 5);

        return view('mobile.samples.checkout', compact('sample', 'customers', 'staffUsers', 'defaultDays'));
    }

    // -----------------------------------------------------------------------
    // CHECKOUT STORE
    // -----------------------------------------------------------------------

    public function storeCheckout(Request $request, string $sampleId)
    {
        $sample = Sample::where('sample_id', $sampleId)->firstOrFail();

        if ($sample->available_qty <= 0) {
            return back()->with('error', 'No copies of this sample are currently available.');
        }

        $validated = $request->validate([
            'checkout_type'  => ['required', 'in:customer,staff'],
            'qty_checked_out'=> ['required', 'integer', 'min:1', 'max:' . $sample->available_qty],
            'due_back_at'    => ['nullable', 'date', 'after_or_equal:today'],
            'destination'    => ['nullable', 'string', 'max:255'],

            // Customer fields
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'customer_name'  => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],

            // Staff fields
            'user_id'        => ['nullable', 'exists:users,id'],
        ]);

        // Require at least a name for customer checkout
        if ($validated['checkout_type'] === 'customer'
            && empty($validated['customer_id'])
            && empty($validated['customer_name'])) {
            return back()->withErrors(['customer_name' => 'Please select a customer or enter a name.'])->withInput();
        }

        // Pull contact info from existing customer if selected
        if (! empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['customer_name']  = $validated['customer_name']  ?: ($customer->company_name ?: $customer->name);
            $validated['customer_phone'] = $validated['customer_phone'] ?: $customer->phone;
            $validated['customer_email'] = $validated['customer_email'] ?: $customer->email;
        }

        SampleCheckout::create($validated + ['sample_id' => $sample->id]);

        // Flip sample status to checked_out if all qty is now out
        if ($sample->fresh()->available_qty <= 0) {
            $sample->update(['status' => 'checked_out']);
        }

        return redirect()->route('mobile.samples.show', $sampleId)
            ->with('success', 'Sample checked out successfully.');
    }
}
