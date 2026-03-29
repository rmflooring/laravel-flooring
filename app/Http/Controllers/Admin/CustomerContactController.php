<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Http\Request;

class CustomerContactController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $customer->contacts()->create($request->only('name', 'title', 'email', 'phone', 'notes'));

        return back()->with('success', 'Contact added.');
    }

    public function update(Request $request, Customer $customer, CustomerContact $contact)
    {
        abort_if($contact->customer_id !== $customer->id, 404);

        $request->validate([
            'name'  => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $contact->update($request->only('name', 'title', 'email', 'phone', 'notes'));

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Customer $customer, CustomerContact $contact)
    {
        abort_if($contact->customer_id !== $customer->id, 404);

        $contact->delete();

        return back()->with('success', 'Contact removed.');
    }
}
