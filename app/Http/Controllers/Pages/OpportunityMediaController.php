<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityDocumentTag;
use App\Models\OpportunityShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

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

        // Gallery tile size — small / medium / large, remembered in a browser cookie for a year
        $perPage = ['small' => 60, 'medium' => 30, 'large' => 24];
        $view    = $request->input('view');
        if (isset($perPage[$view])) {
            Cookie::queue('media_view', $view, 60 * 24 * 365);
        } else {
            $view = $request->cookie('media_view');
            if (! isset($perPage[$view])) {
                $view = 'medium';
            }
        }

        $media = $mediaQuery->paginate($perPage[$view])->withQueryString();

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
            'view'         => $view,
        ]);
    }
}
