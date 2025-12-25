<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxAgency;
use Illuminate\Http\Request;

class TaxAgencyController extends Controller
{
    public function index()
    {
        $agencies = TaxAgency::with('creator')->paginate(15);

        return view('admin.tax_agencies.index', compact('agencies'));
    }

    // Start create function
	public function create()
{
    $months = [
        '' => 'Select Month',
        'January' => 'January',
        'February' => 'February',
        'March' => 'March',
        'April' => 'April',
        'May' => 'May',
        'June' => 'June',
        'July' => 'July',
        'August' => 'August',
        'September' => 'September',
        'October' => 'October',
        'November' => 'November',
        'December' => 'December',
    ];

    $frequencies = [
        '' => 'Select Frequency',
        'Monthly' => 'Monthly',
        'Quarterly' => 'Quarterly',
        'Half-Yearly' => 'Half-Yearly',
        'Yearly' => 'Yearly',
    ];

    $methods = [
        '' => 'Select Method',
        'Accrual' => 'Accrual',
        'Cash' => 'Cash',
    ];

    return view('admin.tax_agencies.create', compact('months', 'frequencies', 'methods'));
}
	//End create function
	//Start store function
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'registration_number' => 'nullable|string|max:100',
        'next_period_month' => 'nullable|in:January,February,March,April,May,June,July,August,September,October,November,December',
        'filing_frequency' => 'nullable|in:Monthly,Quarterly,Half-Yearly,Yearly',
        'reporting_method' => 'nullable|in:Accrual,Cash',
        'collect_on_sales' => 'boolean',
        'pay_on_purchases' => 'boolean',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    TaxAgency::create($request->all());

    return redirect()->route('admin.tax_agencies.index')->with('success', 'Tax Agency created successfully.');
}
	//End store function
	//Start edit function
	public function edit(TaxAgency $taxAgency)
{
    $months = [
        '' => 'Select Month',
        'January' => 'January',
        'February' => 'February',
        'March' => 'March',
        'April' => 'April',
        'May' => 'May',
        'June' => 'June',
        'July' => 'July',
        'August' => 'August',
        'September' => 'September',
        'October' => 'October',
        'November' => 'November',
        'December' => 'December',
    ];

    $frequencies = [
        '' => 'Select Frequency',
        'Monthly' => 'Monthly',
        'Quarterly' => 'Quarterly',
        'Half-Yearly' => 'Half-Yearly',
        'Yearly' => 'Yearly',
    ];

    $methods = [
        '' => 'Select Method',
        'Accrual' => 'Accrual',
        'Cash' => 'Cash',
    ];

    return view('admin.tax_agencies.edit', compact('taxAgency', 'months', 'frequencies', 'methods'));
}

public function update(Request $request, TaxAgency $taxAgency)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'registration_number' => 'nullable|string|max:100',
        'next_period_month' => 'nullable|in:January,February,March,April,May,June,July,August,September,October,November,December',
        'filing_frequency' => 'nullable|in:Monthly,Quarterly,Half-Yearly,Yearly',
        'reporting_method' => 'nullable|in:Accrual,Cash',
        'collect_on_sales' => 'boolean',
        'pay_on_purchases' => 'boolean',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    $taxAgency->update($request->all());

    return redirect()->route('admin.tax_agencies.index')->with('success', 'Tax Agency updated successfully.');
}
	//End edit function
	//Start destroy function
	public function destroy(TaxAgency $taxAgency)
{
    $taxAgency->delete();

    return redirect()->route('admin.tax_agencies.index')
        ->with('success', 'Tax Agency deleted successfully.');
}
	//End destroy funciton
}
