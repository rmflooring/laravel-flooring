<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use App\Models\Setting;
use Illuminate\Http\Request;

class ConditionController extends Controller
{
    public function index()
    {
        $conditions = Condition::orderBy('sort_order')->orderBy('title')->get();

        $defaultEstimateConditionId = (int) Setting::get('default_estimate_condition_id', 0) ?: null;
        $defaultSaleConditionId     = (int) Setting::get('default_sale_condition_id', 0) ?: null;

        return view('admin.conditions.index', compact(
            'conditions',
            'defaultEstimateConditionId',
            'defaultSaleConditionId',
        ));
    }

    public function saveDefaults(Request $request)
    {
        $request->validate([
            'default_estimate_condition_id' => ['nullable', 'integer', 'exists:conditions,id'],
            'default_sale_condition_id'     => ['nullable', 'integer', 'exists:conditions,id'],
        ]);

        Setting::set('default_estimate_condition_id', $request->input('default_estimate_condition_id', ''));
        Setting::set('default_sale_condition_id',     $request->input('default_sale_condition_id', ''));

        return redirect()->route('admin.conditions.index')->with('success', 'Document defaults saved.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        Condition::create([
            'title'      => $request->title,
            'body'       => $request->body,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Condition created.');
    }

    public function edit(Condition $condition)
    {
        return view('admin.conditions.edit', compact('condition'));
    }

    public function update(Condition $condition, Request $request)
    {
        $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $condition->update([
            'title'      => $request->title,
            'body'       => $request->body,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.conditions.index')->with('success', 'Condition updated.');
    }

    public function destroy(Condition $condition)
    {
        $condition->delete();
        return back()->with('success', 'Condition deleted.');
    }
}
