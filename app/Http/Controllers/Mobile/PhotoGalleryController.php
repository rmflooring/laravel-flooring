<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Support\Facades\Storage;

class PhotoGalleryController extends Controller
{
    public function show(Opportunity $opportunity)
    {
        $media = $opportunity->documents()
            ->with('creator')
            ->where('category', 'media')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($doc) {
                $absolute = Storage::disk($doc->disk)->url($doc->path);
                $doc->url = parse_url($absolute, PHP_URL_PATH) ?: $absolute;
                return $doc;
            });

        return view('mobile.photos.show', compact('opportunity', 'media'));
    }
}
