<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;

/**
 * Per-product-type values used by the estimate Excel (Xactimate-style) export:
 * XA Cat, XA Selector and an optional override for the Description column.
 * They apply to material rows only.
 */
class XactimateExportSettingsController extends Controller
{
    public function index()
    {
        $productTypes = ProductType::orderBy('name')
            ->get(['id', 'name', 'status', 'xa_cat', 'xa_selector', 'xa_description_override']);

        return view('admin.settings.xactimate-export', compact('productTypes'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'types'                           => ['array'],
            'types.*.xa_cat'                  => ['nullable', 'string', 'max:50'],
            'types.*.xa_selector'             => ['nullable', 'string', 'max:50'],
            'types.*.xa_description_override' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['types'] ?? [] as $id => $values) {
            ProductType::where('id', (int) $id)->update([
                'xa_cat'                  => $this->clean($values['xa_cat'] ?? null),
                'xa_selector'             => $this->clean($values['xa_selector'] ?? null),
                'xa_description_override' => $this->clean($values['xa_description_override'] ?? null),
            ]);
        }

        return back()->with('success', 'Excel export settings saved.');
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
