<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentStorageService;

class OpportunityFolderService
{
    /**
     * Rename the storage folder (and update all DB paths) when the folder name changes.
     *
     * Derives the current folder directly from the actual document paths in the DB,
     * so this works regardless of which naming convention files were originally stored under
     * (numeric ID, old name, etc.).
     *
     * @param  Opportunity  $opportunity  The already-saved opportunity (jobSiteCustomer must be loaded)
     * @return bool  True if any files were moved, false if nothing changed
     */
    public function renameFolder(Opportunity $opportunity): bool
    {
        $newFolderName = $opportunity->storageFolderName();

        $docs = OpportunityDocument::withTrashed()
            ->where('opportunity_id', $opportunity->id)
            ->get();

        if ($docs->isEmpty()) {
            return false;
        }

        // Determine the old folder name from the first document's actual path.
        // e.g. "opportunities/Sandra_Cokinass - 26-0001/file.jpg" → "Sandra_Cokinass - 26-0001"
        // e.g. "opportunities/123/file.jpg"                        → "123"
        $firstPath  = $docs->first(fn ($d) => $d->path)?->path;
        $pathParts  = $firstPath ? explode('/', $firstPath, 3) : [];
        $oldFolderName = (count($pathParts) === 3 && $pathParts[0] === 'opportunities')
            ? $pathParts[1]
            : null;

        if (! $oldFolderName || $oldFolderName === $newFolderName) {
            return false;
        }

        $anyMoved = false;

        foreach ($docs as $doc) {
            $changed  = false;
            $newPath  = $doc->path;

            if ($doc->path) {
                $parts = explode('/', $doc->path, 3);
                if (count($parts) === 3 && $parts[0] === 'opportunities' && $parts[1] !== $newFolderName) {
                    $newPath = "opportunities/{$newFolderName}/" . $parts[2];
                    $disk    = Storage::disk($doc->disk);
                    if ($disk->exists($doc->path)) {
                        $disk->move($doc->path, $newPath);
                        DocumentStorageService::forceOpenPermissions($doc->disk, $newPath);
                    } else {
                        Log::warning("OpportunityFolderService: main file not found on disk, updating DB only: {$doc->path}");
                    }
                    $changed = true;
                }
            }

            $newThumbs = [];
            foreach (['thumbnail_path', 'thumbnail_sm_path'] as $col) {
                $thumb = $doc->{$col};
                if (! $thumb) {
                    continue;
                }
                $parts = explode('/', $thumb, 3);
                if (count($parts) === 3 && $parts[0] === 'opportunities' && $parts[1] !== $newFolderName) {
                    $newThumbs[$col] = "opportunities/{$newFolderName}/" . $parts[2];
                    $disk = Storage::disk($doc->disk);
                    if ($disk->exists($thumb)) {
                        $disk->move($thumb, $newThumbs[$col]);
                        DocumentStorageService::forceOpenPermissions($doc->disk, $newThumbs[$col]);
                    }
                    $changed = true;
                }
            }

            if ($changed) {
                OpportunityDocument::withoutTimestamps(fn () => $doc->update(array_merge([
                    'path' => $newPath,
                ], $newThumbs)));
                $anyMoved = true;
            }
        }

        if ($anyMoved) {
            Log::info("OpportunityFolderService: renamed folder for opportunity #{$opportunity->id}: '{$oldFolderName}' → '{$newFolderName}'");

            // Mirror the folder rename to OneDrive (best-effort)
            app(GraphOneDriveService::class)->renameFolder(
                "opportunities/{$oldFolderName}",
                $newFolderName
            );
        }

        return $anyMoved;
    }
}
