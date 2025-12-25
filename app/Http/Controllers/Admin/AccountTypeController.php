<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    public function index()
    {
        $types = AccountType::with('creator')->paginate(15);

        return view('admin.account_types.index', compact('types'));
    }

	//start create function
public function create()
{
    return view('admin.account_types.create');
}
	//end create function
	//start store function
public function store(Request $request)
{
    $request->validate([
    'name' => 'required|string|max:255',
    'category' => 'required|in:Asset,Liability,Equity,Income,Expense',
    'description' => 'nullable|string',
    'status' => 'required|in:active,inactive',
]);

    AccountType::create($request->all());

    return redirect()->route('admin.account_types.index')->with('success', 'Account Type created successfully.');
}
	//end store function
   	 // start edit function 
public function edit(AccountType $accountType)
{
    return view('admin.account_types.edit', compact('accountType'));
}

public function update(Request $request, AccountType $accountType)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'category' => 'required|in:Asset,Liability,Equity,Income,Expense',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    $accountType->update($request->all());

    return redirect()->route('admin.account_types.index')
        ->with('success', 'Account Type updated successfully.');
}
	//end edit function
	//start destroy function 
public function destroy(AccountType $accountType)
{
    $accountType->delete();

    return redirect()->route('admin.account_types.index')
        ->with('success', 'Account Type deleted successfully.');
}
	//end destroy function

}
