<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityShare;
use Illuminate\Http\Request;

class OpportunityShareController extends Controller
{
    public function store(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'document_ids'   => 'required|array|min:1',
            'document_ids.*' => 'integer|exists:opportunity_documents,id',
            'label'          => 'nullable|string|max:255',
            'expires_at'     => 'nullable|date|after:now',
        ]);

        $share = OpportunityShare::create([
            'opportunity_id' => $opportunity->id,
            'label'          => $validated['label'] ?? null,
            'created_by'     => auth()->id(),
            'expires_at'     => $validated['expires_at'] ?? null,
        ]);

        $share->documents()->attach($validated['document_ids']);

        return redirect()
            ->route('pages.opportunities.media.index', $opportunity)
            ->with('share_created', $share->publicUrl())
            ->with('success', 'Share link created.');
    }

    public function destroy(Opportunity $opportunity, OpportunityShare $share)
    {
        abort_if($share->opportunity_id !== $opportunity->id, 403);

        $share->delete();

        return back()->with('success', 'Share link revoked.');
    }
}
