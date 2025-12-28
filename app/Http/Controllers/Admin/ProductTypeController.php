<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitMeasure;
use App\Models\GLAccount;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{

    //Start index function
public function index()
{
    $productTypes = ProductType::with(['orderedByUnit', 'soldByUnit', 'defaultCostGlAccount', 'defaultSellGlAccount'])->get();

    return view('admin.product_types.index', compact('productTypes'));
}
    //End index function

    public function create()
    {
        $unitMeasures = UnitMeasure::all(['id', 'label']);
        $glAccounts = GLAccount::all(['id', 'account_number', 'name']);

        return view('admin.product_types.create', compact('unitMeasures', 'glAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ordered_by_unit_id' => 'required|exists:unit_measures,id',
            'sold_by_unit_id' => 'required|exists:unit_measures,id',
            'default_cost_gl_account_id' => 'nullable|exists:gl_accounts,id',
            'default_sell_gl_account_id' => 'nullable|exists:gl_accounts,id',
        ]);

        ProductType::create($validated);

        return redirect()->route('admin.product_types.index')
            ->with('success', 'Product Type created successfully!');
    }

   /**
 * Show the form for editing the specified product type.
 */
public function edit($id)
{
    $productType = ProductType::findOrFail($id);
    
    $unitMeasures = UnitMeasure::all(['id', 'label']);
    $glAccounts   = GLAccount::all(['id', 'account_number', 'name']);

    return view('admin.product_types.edit', compact('productType', 'unitMeasures', 'glAccounts'));
}

/**
 * Update the specified product type in storage.
 */
public function update(Request $request, $id)
{
    $productType = ProductType::findOrFail($id);

    $validated = $request->validate([
        'name'                        => 'required|string|max:255',
        'ordered_by_unit_id'          => 'required|exists:unit_measures,id',
        'sold_by_unit_id'             => 'required|exists:unit_measures,id',
        'default_cost_gl_account_id'  => 'nullable|exists:gl_accounts,id',
        'default_sell_gl_account_id'  => 'nullable|exists:gl_accounts,id',
    ]);

    $productType->update([
        ...$validated,
        'updated_by' => auth()->id(),  // if you want to track who updated
    ]);

    return redirect()->route('admin.product_types.index')
        ->with('success', 'Product type updated successfully!');
}

/**
 * Remove the specified product type from storage.
 */
public function destroy($id)
{
    $productType = ProductType::findOrFail($id);

    // Optional safety: prevent deletion if related product lines exist
    // if ($productType->productLines()->exists()) {
    //     return redirect()->back()->with('error', 'Cannot delete: Product type has associated product lines.');
    // }

    $productType->delete();

    return redirect()->route('admin.product_types.index')
        ->with('success', 'Product type deleted successfully!');
}

}
