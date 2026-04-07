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
        $uploadedBy   = $request->input('uploaded_by');

        $mediaQuery = $opportunity->documents()
            ->withTrashed()
            ->with(['creator'])
            ->where('category', 'media')
            ->orderByDesc('created_at');

        if (!$showArchived) {
            $mediaQuery->whereNull('deleted_at');
        }

        if ($uploadedBy) {
            $mediaQuery->where('created_by', $uploadedBy);
        }

        $media = $mediaQuery->paginate(60)->withQueryString();

        // Build uploader list from all media for this opportunity (unfiltered, for the dropdown)
        $uploaderIds = $opportunity->documents()
            ->where('category', 'media')
            ->whereNotNull('created_by')
            ->distinct()
            ->pluck('created_by');

        $uploaders = \App\Models\User::whereIn('id', $uploaderIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.opportunities.media.index', [
            'opportunity'  => $opportunity,
            'media'        => $media,
            'showArchived' => $showArchived,
            'uploaders'    => $uploaders,
            'uploadedBy'   => $uploadedBy,
        ]);
    }
}
