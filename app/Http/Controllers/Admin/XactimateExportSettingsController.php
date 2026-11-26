<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreightItem;
use App\Models\ProductType;
use Illuminate\Http\Request;

/**
 * Values used by the estimate Excel (Xactimate-style) export: XA Cat, XA Selector
 * and an optional override for the Description column. Set per product type
 * (applies to material rows) and per freight item (applies to freight rows).
 */
class XactimateExportSettingsController extends Controller
{
    private const FIELDS = ['xa_cat', 'xa_selector', 'xa_description_override'];

    public function index()
    {
        $productTypes = ProductType::orderBy('name')
            ->get(['id', 'name', 'status', ...self::FIELDS]);

        $freightItems = FreightItem::orderBy('description')
            ->get(['id', 'description', ...self::FIELDS]);

        return view('admin.settings.xactimate-export', compact('productTypes', 'freightItems'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (['types', 'freight'] as $group) {
            $rules[$group]                                = ['array'];
            $rules["{$group}.*.xa_cat"]                   = ['nullable', 'string', 'max:50'];
            $rules["{$group}.*.xa_selector"]              = ['nullable', 'string', 'max:50'];
            $rules["{$group}.*.xa_description_override"] = ['nullable', 'string', 'max:500'];
        }
        $validated = $request->validate($rules);

        foreach ($validated['types'] ?? [] as $id => $values) {
            ProductType::where('id', (int) $id)->update($this->values($values));
        }

        foreach ($validated['freight'] ?? [] as $id => $values) {
            FreightItem::where('id', (int) $id)->update($this->values($values));
        }

        return back()->with('success', 'Excel export settings saved.');
    }

    private function values(array $input): array
    {
        $out = [];
        foreach (self::FIELDS as $field) {
            $value       = trim((string) ($input[$field] ?? ''));
            $out[$field] = $value === '' ? null : $value;
        }

        return $out;
    }
}
