<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->get('search');
        $showInactive = $request->boolean('show_inactive');

        $query = PaymentTerm::withCount('invoices');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (! $showInactive) {
            $query->where('is_active', true);
        }

        $terms = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.payment-terms.index', compact('terms', 'search', 'showInactive'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:payment_terms,name'],
            'days'        => ['nullable', 'integer', 'min:0', 'max:365'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_active'] = true;

        PaymentTerm::create($data);

        return back()->with('success', 'Payment term created.');
    }

    public function edit(PaymentTerm $paymentTerm)
    {
        return view('admin.payment-terms.edit', compact('paymentTerm'));
    }

    public function update(Request $request, PaymentTerm $paymentTerm)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:payment_terms,name,' . $paymentTerm->id],
            'days'        => ['nullable', 'integer', 'min:0', 'max:365'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        $paymentTerm->update($data);

        return redirect()->route('admin.payment-terms.index')
            ->with('success', 'Payment term updated.');
    }

    public function destroy(PaymentTerm $paymentTerm)
    {
        if ($paymentTerm->invoices()->count() > 0) {
            return back()->with('error', 'Cannot delete a payment term that is used on invoices. Deactivate it instead.');
        }

        $paymentTerm->delete();

        return back()->with('success', 'Payment term deleted.');
    }
}
