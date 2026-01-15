<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityMediaController extends Controller
{
    public function index(Opportunity $opportunity, Request $request)
    {
        $showArchived = $request->boolean('show_archived');

        $mediaQuery = $opportunity->documents()
            ->withTrashed()
            ->with('label')
            ->where('category', 'media')
            ->orderByDesc('created_at');

        if (!$showArchived) {
            $mediaQuery->whereNull('deleted_at');
        }

        $media = $mediaQuery->paginate(60)->withQueryString();

        return view('pages.opportunities.media.index', [
            'opportunity'  => $opportunity,
            'media'        => $media,
            'showArchived' => $showArchived,
        ]);
    }
}
