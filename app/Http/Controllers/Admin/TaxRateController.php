<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxAgency;
use App\Models\GLAccount;
use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function create()
    {
        $agencies = TaxAgency::all(['id', 'name']);
        $glAccounts = GLAccount::all(['id', 'account_number', 'name']);

        return view('admin.tax_rates.create', compact('agencies', 'glAccounts'));
    }

//added index function
public function index()
{
    $taxRates = TaxRate::with(['agency', 'salesGlAccount', 'purchaseGlAccount'])->get();

    return view('admin.tax_rates.index', compact('taxRates'));
}
//ended index

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tax_agency_id' => 'required|exists:tax_agencies,id',
            'collect_on_sales' => 'boolean',
            'sales_rate' => 'nullable|numeric|min:0|max:100|required_if:collect_on_sales,true',
            'sales_gl_account_id' => 'nullable|exists:gl_accounts,id|required_if:collect_on_sales,true',
            'pay_on_purchases' => 'boolean',
            'purchase_rate' => 'nullable|numeric|min:0|max:100|required_if:pay_on_purchases,true',
            'purchase_gl_account_id' => 'nullable|exists:gl_accounts,id|required_if:pay_on_purchases,true',
            'show_on_return_line' => 'boolean',
        ]);

        TaxRate::create($validated);

        return redirect()->route('admin.tax_rates.index')->with('success', 'Tax Rate created successfully!');
    }

    // Start edit function
public function edit(TaxRate $taxRate)
{
    $agencies = TaxAgency::all(['id', 'name']);
    $glAccounts = GLAccount::all(['id', 'account_number', 'name']);

    return view('admin.tax_rates.edit', compact('taxRate', 'agencies', 'glAccounts'));
}

public function update(Request $request, TaxRate $taxRate)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'tax_agency_id' => 'required|exists:tax_agencies,id',
        'collect_on_sales' => 'boolean',
        'sales_rate' => 'nullable|numeric|min:0|max:100|required_if:collect_on_sales,true',
        'sales_gl_account_id' => 'nullable|exists:gl_accounts,id|required_if:collect_on_sales,true',
        'pay_on_purchases' => 'boolean',
        'purchase_rate' => 'nullable|numeric|min:0|max:100|required_if:pay_on_purchases,true',
        'purchase_gl_account_id' => 'nullable|exists:gl_accounts,id|required_if:pay_on_purchases,true',
        'show_on_return_line' => 'boolean',
    ]);

    $taxRate->update($validated);

    return redirect()->route('admin.tax_rates.index')->with('success', 'Tax Rate updated successfully!');
}
   //End edit function
   //Start destroy function
public function destroy(TaxRate $taxRate)
{
    $taxRate->delete();

    return redirect()->route('admin.tax_rates.index')->with('success', 'Tax Rate deleted successfully!');
}
  //End destroy function 
}
