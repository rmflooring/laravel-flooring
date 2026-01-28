<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GLAccount;
use Illuminate\Http\Request;

class GLAccountController extends Controller
{
    public function index()
    {
        $accounts = GLAccount::with(['accountType', 'detailType', 'parent', 'creator'])->paginate(15);

        return view('admin.gl_accounts.index', compact('accounts'));
    }

    // We'll add create/store/edit/update/destroy in the next steps
	public function create()
{
    $accountTypes = \App\Models\AccountType::where('status', 'active')->get();
    $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];

    return view('admin.gl_accounts.create', compact('accountTypes', 'statusOptions'));
}

public function store(Request $request)
{
    $request->validate([
        'account_number' => 'required|string|max:50|unique:gl_accounts,account_number',
        'name' => 'required|string|max:255',
        'account_type_id' => 'required|exists:account_types,id',
        'detail_type_id' => 'required|exists:detail_types,id',
        'parent_id' => 'nullable|exists:gl_accounts,id',
        'description' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ]);

    GLAccount::create($request->all());

    return redirect()->route('admin.gl_accounts.index')->with('success', 'GL Account created successfully.');
}

// Ajax: Get detail types by account type
public function getDetailTypes(Request $request)
{
    $accountTypeId = $request->account_type_id;

    $detailTypes = \App\Models\DetailType::where('account_type_id', $accountTypeId)
        ->where('status', 'active')
        ->get(['id', 'name']);

    return response()->json($detailTypes);
}

// Ajax: Get possible parent accounts by account type (exclude self for edit later)
public function getParentAccounts(Request $request)
{
    $accountTypeId = $request->account_type_id;

    $parents = \App\Models\GLAccount::where('account_type_id', $accountTypeId)
        ->where('status', 'active')
        ->get(['id', 'account_number', 'name']);

    return response()->json($parents);
}

public function edit(GLAccount $gl_account)
{
    $accountTypes = \App\Models\AccountType::where('status', 'active')->get();
    $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];

    return view('admin.gl_accounts.edit', [
        'glAccount'     => $gl_account,
        'accountTypes' => $accountTypes,
        'statusOptions'=> $statusOptions,
    ]);
}
	
}
