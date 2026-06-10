<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use Illuminate\Http\Request;

class OpportunityNoteController extends Controller
{
    public function store(Request $request, Opportunity $opportunity)
    {
        $request->validate(['body' => 'required|string|max:5000']);

        $opportunity->notes()->create([
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        return back()->with('success', 'Note added.');
    }

    public function update(Request $request, Opportunity $opportunity, OpportunityNote $note)
    {
        abort_unless($note->opportunity_id === $opportunity->id, 404);
        abort_unless(
            auth()->id() === $note->user_id || auth()->user()->hasRole('admin'),
            403
        );

        $request->validate(['body' => 'required|string|max:5000']);

        $note->update(['body' => $request->body]);

        return back()->with('success', 'Note updated.');
    }

    public function destroy(Opportunity $opportunity, OpportunityNote $note)
    {
        abort_unless($note->opportunity_id === $opportunity->id, 404);
        abort_unless(
            auth()->id() === $note->user_id || auth()->user()->hasRole('admin'),
            403
        );

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
