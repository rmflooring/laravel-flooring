<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function uploadPhotos(Opportunity $opportunity, Request $request)
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'image', 'max:20480'],
        ]);

        $userId = auth()->id();
        $count  = 0;

        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';
            $disk = DocumentStorageService::disk();
            $path = $file->store("opportunities/{$opportunity->storageFolderName()}", $disk);

            OpportunityDocument::create([
                'opportunity_id' => $opportunity->id,
                'disk'           => $disk,
                'path'           => $path,
                'original_name'  => $file->getClientOriginalName(),
                'stored_name'    => basename($path),
                'mime_type'      => $mime,
                'extension'      => $file->getClientOriginalExtension(),
                'size_bytes'     => $file->getSize(),
                'category'       => 'media',
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);

            $count++;
        }

        Log::info('[Mobile Photos] Photos uploaded', [
            'opportunity_id' => $opportunity->id,
            'count'          => $count,
            'user_id'        => $userId,
        ]);

        $photosUrl = route('mobile.opportunity.photos', $opportunity);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $photosUrl, 'count' => $count]);
        }

        return redirect()->to($photosUrl)
            ->with('success', $count . ' photo' . ($count !== 1 ? 's' : '') . ' uploaded.');
    }
}
