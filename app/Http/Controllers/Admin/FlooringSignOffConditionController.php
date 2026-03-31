<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlooringSignOffCondition;
use Illuminate\Http\Request;

class FlooringSignOffConditionController extends Controller
{
    public function index()
    {
        $conditions = FlooringSignOffCondition::orderBy('sort_order')->orderBy('title')->get();
        return view('admin.flooring-sign-off-conditions.index', compact('conditions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        FlooringSignOffCondition::create([
            'title'      => $request->title,
            'body'       => $request->body,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Condition created.');
    }

    public function edit(FlooringSignOffCondition $flooringSignOffCondition)
    {
        return view('admin.flooring-sign-off-conditions.edit', [
            'condition' => $flooringSignOffCondition,
        ]);
    }

    public function update(FlooringSignOffCondition $flooringSignOffCondition, Request $request)
    {
        $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $flooringSignOffCondition->update([
            'title'      => $request->title,
            'body'       => $request->body,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.flooring-conditions.index')
            ->with('success', 'Condition updated.');
    }

    public function destroy(FlooringSignOffCondition $flooringSignOffCondition)
    {
        $flooringSignOffCondition->delete();
        return back()->with('success', 'Condition deleted.');
    }
}
