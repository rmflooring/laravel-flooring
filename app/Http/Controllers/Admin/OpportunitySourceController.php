<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpportunitySource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunitySourceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $showInactive = $request->boolean('show_inactive');

        $query = OpportunitySource::withCount('opportunities');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (!$showInactive) {
            $query->where('is_active', true);
        }

        $sources = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.opportunity_sources.index', compact('sources', 'search', 'showInactive'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:opportunity_sources,name'],
        ]);

        $data['is_active'] = true;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        OpportunitySource::create($data);

        return back()->with('success', 'Source created.');
    }

    public function edit(OpportunitySource $opportunitySource)
    {
        return view('admin.opportunity_sources.edit', [
            'source' => $opportunitySource,
        ]);
    }

    public function update(Request $request, OpportunitySource $opportunitySource)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:opportunity_sources,name,' . $opportunitySource->id],
            'is_active' => ['boolean'],
        ]);

        $data['updated_by'] = Auth::id();

        $opportunitySource->update($data);

        return redirect()->route('admin.opportunity_sources.index')
            ->with('success', 'Source updated.');
    }

    public function destroy(OpportunitySource $opportunitySource)
    {
        if ($opportunitySource->opportunities()->count() > 0) {
            return back()->with('error', 'Cannot delete a source that is assigned to opportunities. Deactivate it instead.');
        }

        $opportunitySource->delete();

        return back()->with('success', 'Source deleted.');
    }
}
