<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\UnitMeasure;
use App\Models\GLAccount;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view product types')->only(['index']);
        $this->middleware('permission:create product types')->only(['create', 'store']);
        $this->middleware('permission:edit product types')->only(['edit', 'update']);
        $this->middleware('permission:delete product types')->only(['destroy']);
    }


	public function index(Request $request)
	{
		$search = trim((string) $request->query('search', ''));

		$productTypes = ProductType::with([
				'orderedByUnit',
				'soldByUnit',
				'defaultCostGlAccount',
				'defaultSellGlAccount',
			])
			->when($search !== '', function ($query) use ($search) {
				$query->where('name', 'like', "%{$search}%");
			})
			->orderBy('name')
			->paginate(25)
			->withQueryString();

		return view('admin.product_types.index', compact('productTypes', 'search'));
	}


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

        return redirect()
            ->route('admin.product_types.index')
            ->with('success', 'Product type created successfully.');
    }

    public function edit($id)
    {
        $productType = ProductType::findOrFail($id);
        $unitMeasures = UnitMeasure::all(['id', 'label']);
        $glAccounts = GLAccount::all(['id', 'account_number', 'name']);

        return view('admin.product_types.edit', compact(
            'productType',
            'unitMeasures',
            'glAccounts'
        ));
    }

    public function update(Request $request, $id)
    {
        $productType = ProductType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ordered_by_unit_id' => 'required|exists:unit_measures,id',
            'sold_by_unit_id' => 'required|exists:unit_measures,id',
            'default_cost_gl_account_id' => 'nullable|exists:gl_accounts,id',
            'default_sell_gl_account_id' => 'nullable|exists:gl_accounts,id',
        ]);

        $productType->update($validated);

        return redirect()
            ->route('admin.product_types.index')
            ->with('success', 'Product type updated successfully.');
    }

    public function destroy($id)
    {
        $productType = ProductType::findOrFail($id);
        $productType->delete();

        return redirect()
            ->route('admin.product_types.index')
            ->with('success', 'Product type deleted successfully.');
    }
}
