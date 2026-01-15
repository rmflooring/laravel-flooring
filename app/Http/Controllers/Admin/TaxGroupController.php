<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaxGroupController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('show_archived');

        $query = DB::table('tax_rate_groups')
            ->select('tax_rate_groups.*');

        if ($showArchived) {
            // include archived + active
        } else {
            $query->whereNull('tax_rate_groups.deleted_at');
        }

        $groups = $query
            ->orderBy('tax_rate_groups.name')
            ->paginate(25)
            ->withQueryString();

        // Get default group id (single-row table pattern)
        $defaultGroupId = DB::table('default_tax')->where('id', 1)->value('tax_rate_group_id');

        return view('admin.tax_groups.index', [
            'groups' => $groups,
            'showArchived' => $showArchived,
            'defaultGroupId' => $defaultGroupId,
        ]);
    }

    public function create()
    {
        $taxRates = DB::table('tax_rates')
            ->select('id', 'name', 'sales_rate', 'tax_agency_id')
            ->orderBy('name')
            ->get();

        return view('admin.tax_groups.create', [
            'taxRates' => $taxRates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tax_rate_ids' => ['required', 'array', 'min:1'],
            'tax_rate_ids.*' => ['integer', 'exists:tax_rates,id'],
            'make_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            $userId = Auth::id();

            $groupId = DB::table('tax_rate_groups')->insertGetId([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            foreach ($validated['tax_rate_ids'] as $rateId) {
                $rows[] = [
                    'tax_rate_group_id' => $groupId,
                    'tax_rate_id' => $rateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('tax_rate_group_items')->insert($rows);

            if (!empty($validated['make_default'])) {
                // Single-row default: always id=1
                DB::table('default_tax')->updateOrInsert(
                    ['id' => 1],
                    [
                        'tax_rate_group_id' => $groupId,
                        'updated_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.tax_groups.index')
            ->with('success', 'Tax group created successfully.');
    }

    public function show($tax_group)
    {
        // Optional later. For now, redirect to edit.
        return redirect()->route('admin.tax_groups.edit', $tax_group);
    }

    public function edit($tax_group)
    {
        $group = DB::table('tax_rate_groups')->where('id', $tax_group)->first();
        abort_if(!$group, 404);

        $selectedRateIds = DB::table('tax_rate_group_items')
            ->where('tax_rate_group_id', $tax_group)
            ->pluck('tax_rate_id')
            ->all();

        $taxRates = DB::table('tax_rates')
            ->select('id', 'name', 'sales_rate', 'tax_agency_id')
            ->orderBy('name')
            ->get();

        $defaultGroupId = DB::table('default_tax')->where('id', 1)->value('tax_rate_group_id');

        return view('admin.tax_groups.edit', [
            'group' => $group,
            'taxRates' => $taxRates,
            'selectedRateIds' => $selectedRateIds,
            'defaultGroupId' => $defaultGroupId,
        ]);
    }

    public function update(Request $request, $tax_group)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'tax_rate_ids' => ['required', 'array', 'min:1'],
            'tax_rate_ids.*' => ['integer', 'exists:tax_rates,id'],
            'make_default' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $tax_group) {
            $userId = Auth::id();

            DB::table('tax_rate_groups')
                ->where('id', $tax_group)
                ->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);

            // Replace pivot items
            DB::table('tax_rate_group_items')->where('tax_rate_group_id', $tax_group)->delete();

            $rows = [];
            foreach ($validated['tax_rate_ids'] as $rateId) {
                $rows[] = [
                    'tax_rate_group_id' => $tax_group,
                    'tax_rate_id' => $rateId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('tax_rate_group_items')->insert($rows);

            if (!empty($validated['make_default'])) {
                DB::table('default_tax')->updateOrInsert(
                    ['id' => 1],
                    [
                        'tax_rate_group_id' => $tax_group,
                        'updated_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.tax_groups.index')
            ->with('success', 'Tax group updated successfully.');
    }

    public function destroy($tax_group)
    {
        // Soft delete (since table has softDeletes)
        DB::table('tax_rate_groups')
            ->where('id', $tax_group)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        return redirect()
            ->route('admin.tax_groups.index')
            ->with('success', 'Tax group archived successfully.');
    }
	
	public function restore($tax_group)
	{
		$group = DB::table('tax_rate_groups')->where('id', $tax_group)->first();
		abort_if(!$group, 404);

		// If already active, nothing to do
		if (is_null($group->deleted_at)) {
			return redirect()
				->route('admin.tax_groups.index')
				->with('success', 'Tax group is already active.');
		}

		DB::table('tax_rate_groups')
			->where('id', $tax_group)
			->update([
				'deleted_at' => null,
				'updated_at' => now(),
				'updated_by' => Auth::id(),
			]);

		return redirect()
			->route('admin.tax_groups.index')
			->with('success', 'Tax group restored successfully.');
	}

}
