<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityDocumentTag;
use App\Models\OpportunityShare;
use Illuminate\Http\Request;

class OpportunityMediaController extends Controller
{
    public function index(Opportunity $opportunity, Request $request)
    {
        $showArchived = $request->boolean('show_archived');
        $uploadedBy   = $request->input('uploaded_by');
        $tagId        = $request->input('tag_id');

        $mediaQuery = $opportunity->documents()
            ->withTrashed()
            ->with(['creator', 'tags'])
            ->where('category', 'media')
            ->orderByDesc('created_at');

        if (!$showArchived) {
            $mediaQuery->whereNull('deleted_at');
        }

        if ($uploadedBy) {
            $mediaQuery->where('created_by', $uploadedBy);
        }

        if ($tagId) {
            $mediaQuery->whereHas('tags', fn ($q) => $q->where('opportunity_document_tags.id', $tagId));
        }

        $media = $mediaQuery->paginate(30)->withQueryString();

        // Build uploader list from all media for this opportunity (unfiltered, for the dropdown)
        $uploaderIds = $opportunity->documents()
            ->where('category', 'media')
            ->whereNotNull('created_by')
            ->distinct()
            ->pluck('created_by');

        $uploaders = \App\Models\User::whereIn('id', $uploaderIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $shares = OpportunityShare::where('opportunity_id', $opportunity->id)
            ->with(['documents', 'createdBy'])
            ->latest()
            ->get();

        $tags = OpportunityDocumentTag::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.opportunities.media.index', [
            'opportunity'  => $opportunity,
            'media'        => $media,
            'showArchived' => $showArchived,
            'uploaders'    => $uploaders,
            'uploadedBy'   => $uploadedBy,
            'shares'       => $shares,
            'tags'         => $tags,
            'tagId'        => $tagId,
        ]);
    }
}
