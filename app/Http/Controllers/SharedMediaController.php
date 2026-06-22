<?php

namespace App\Http\Controllers;

use App\Models\OpportunityShare;

class SharedMediaController extends Controller
{
    public function show(string $token)
    {
        $share = OpportunityShare::where('token', $token)
            ->with(['opportunity', 'documents', 'createdBy'])
            ->firstOrFail();

        if ($share->isExpired()) {
            return view('share.expired');
        }

        return view('share.show', compact('share'));
    }
}
