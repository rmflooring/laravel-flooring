<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpportunityDocumentLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityDocumentLabelController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $showInactive = $request->boolean('show_inactive');

        $query = OpportunityDocumentLabel::withCount('documents');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (!$showInactive) {
            $query->where('is_active', true);
        }

        $labels = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.opportunity_document_labels.index', compact('labels', 'search', 'showInactive'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:opportunity_document_labels,name'],
        ]);

        $data['is_active'] = true;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        OpportunityDocumentLabel::create($data);

        return back()->with('success', 'Label created.');
    }

    public function edit(OpportunityDocumentLabel $opportunityDocumentLabel)
    {
        return view('admin.opportunity_document_labels.edit', [
            'label' => $opportunityDocumentLabel,
        ]);
    }

    public function update(Request $request, OpportunityDocumentLabel $opportunityDocumentLabel)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:opportunity_document_labels,name,' . $opportunityDocumentLabel->id],
            'is_active' => ['boolean'],
        ]);

        $data['updated_by'] = Auth::id();

        $opportunityDocumentLabel->update($data);

        return redirect()->route('admin.opportunity_document_labels.index')
            ->with('success', 'Label updated.');
    }

    public function destroy(OpportunityDocumentLabel $opportunityDocumentLabel)
    {
        if ($opportunityDocumentLabel->documents()->count() > 0) {
            return back()->with('error', 'Cannot delete a label that is assigned to documents. Deactivate it instead.');
        }

        $opportunityDocumentLabel->delete();

        return back()->with('success', 'Label deleted.');
    }
}
