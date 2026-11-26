<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreightItem;
use App\Models\LabourItem;
use App\Models\LabourType;
use App\Models\ProductType;
use Illuminate\Http\Request;

/**
 * Values used by the estimate Excel (Xactimate-style) export: XA Cat, XA Selector
 * and an optional override for the Description column. Set per product type
 * (material rows), per labour type (labour rows) and per freight item (freight rows).
 * Individual labour items can also carry a description override only; it takes
 * precedence over their labour type's override.
 */
class XactimateExportSettingsController extends Controller
{
    private const FIELDS = ['xa_cat', 'xa_selector', 'xa_description_override'];

    /** Form field name => model class */
    private const GROUPS = [
        'types'   => ProductType::class,
        'labour'  => LabourType::class,
        'freight' => FreightItem::class,
    ];

    public function index()
    {
        $productTypes = ProductType::orderBy('name')
            ->get(['id', 'name', 'status', ...self::FIELDS]);

        $labourTypes = LabourType::orderBy('name')
            ->get(['id', 'name', ...self::FIELDS]);

        $freightItems = FreightItem::orderBy('description')
            ->get(['id', 'description', ...self::FIELDS]);

        $labourItemOverrides = LabourItem::with('labourType:id,name')
            ->whereNotNull('xa_description_override')
            ->orderBy('description')
            ->get(['id', 'description', 'labour_type_id', 'xa_description_override'])
            ->map(fn (LabourItem $item) => $this->labourItemRow($item))
            ->values();

        return view('admin.settings.xactimate-export', compact(
            'productTypes', 'labourTypes', 'freightItems', 'labourItemOverrides'
        ));
    }

    /** Search box for the labour item overrides section. */
    public function searchLabourItems(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        // Every word must appear in the description or the labour type name,
        // so "laminate click" finds "Laminate Install - Click"
        $query = LabourItem::with('labourType:id,name');
        foreach (preg_split('/\s+/', $q) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('description', 'like', $like)
                  ->orWhereHas('labourType', fn ($t) => $t->where('name', 'like', $like));
            });
        }

        $items = $query
            ->orderBy('description')
            ->limit(25)
            ->get(['id', 'description', 'labour_type_id', 'xa_description_override']);

        return response()->json($items->map(fn (LabourItem $item) => $this->labourItemRow($item))->values());
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (array_keys(self::GROUPS) as $group) {
            $rules[$group]                                = ['array'];
            $rules["{$group}.*.xa_cat"]                   = ['nullable', 'string', 'max:50'];
            $rules["{$group}.*.xa_selector"]              = ['nullable', 'string', 'max:50'];
            $rules["{$group}.*.xa_description_override"] = ['nullable', 'string', 'max:500'];
        }
        $rules['labour_items']                             = ['array'];
        $rules['labour_items.*.xa_description_override'] = ['nullable', 'string', 'max:500'];

        $validated = $request->validate($rules);

        foreach (self::GROUPS as $group => $model) {
            foreach ($validated[$group] ?? [] as $id => $values) {
                $model::where('id', (int) $id)->update($this->values($values));
            }
        }

        // Labour item overrides: the submitted list is the whole list. Anything not
        // submitted (removed in the UI) or left blank is cleared. Guarded by a marker
        // field so a request without that section never wipes existing overrides.
        if ($request->boolean('labour_items_present')) {
            $keep = [];
            foreach ($validated['labour_items'] ?? [] as $id => $values) {
                $override = trim((string) ($values['xa_description_override'] ?? ''));
                if ($override === '') {
                    continue;
                }
                LabourItem::where('id', (int) $id)->update(['xa_description_override' => $override]);
                $keep[] = (int) $id;
            }

            LabourItem::whereNotNull('xa_description_override')
                ->whereNotIn('id', $keep ?: [0])
                ->update(['xa_description_override' => null]);
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

    private function labourItemRow(LabourItem $item): array
    {
        return [
            'id'          => $item->id,
            'description' => $item->description,
            'labour_type' => $item->labourType?->name,
            'override'    => $item->xa_description_override ?? '',
        ];
    }
}
