<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpportunityDocumentTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityDocumentTagController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $showInactive = $request->boolean('show_inactive');

        $query = OpportunityDocumentTag::withCount('documents');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (!$showInactive) {
            $query->where('is_active', true);
        }

        $tags = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.opportunity_document_tags.index', compact('tags', 'search', 'showInactive'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:opportunity_document_tags,name'],
        ]);

        $data['is_active'] = true;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        OpportunityDocumentTag::create($data);

        return back()->with('success', 'Tag created.');
    }

    public function edit(OpportunityDocumentTag $opportunityDocumentTag)
    {
        return view('admin.opportunity_document_tags.edit', [
            'tag' => $opportunityDocumentTag,
        ]);
    }

    public function update(Request $request, OpportunityDocumentTag $opportunityDocumentTag)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:opportunity_document_tags,name,' . $opportunityDocumentTag->id],
            'is_active' => ['boolean'],
        ]);

        $data['updated_by'] = Auth::id();

        $opportunityDocumentTag->update($data);

        return redirect()->route('admin.opportunity_document_tags.index')
            ->with('success', 'Tag updated.');
    }

    public function destroy(OpportunityDocumentTag $opportunityDocumentTag)
    {
        if ($opportunityDocumentTag->documents()->count() > 0) {
            return back()->with('error', 'Cannot delete a tag that is assigned to documents. Deactivate it instead.');
        }

        $opportunityDocumentTag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
