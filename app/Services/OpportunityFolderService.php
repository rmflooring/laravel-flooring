<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpportunityFolderService
{
    /**
     * Rename the storage folder (and update all DB paths) if the folder name has changed.
     *
     * Call this after saving the opportunity (and after reloading the jobSiteCustomer
     * relationship if the job_site_customer_id was changed).
     *
     * @param  Opportunity  $opportunity  The already-saved opportunity (with updated attributes)
     * @param  string       $oldFolderName  The folder name computed BEFORE the save
     * @return bool  True if a rename was performed, false if nothing changed
     */
    public function renameFolder(Opportunity $opportunity, string $oldFolderName): bool
    {
        $newFolderName = $opportunity->storageFolderName();

        if ($oldFolderName === $newFolderName) {
            return false;
        }

        $oldPrefix = "opportunities/{$oldFolderName}";
        $newPrefix = "opportunities/{$newFolderName}";

        $docs = OpportunityDocument::withTrashed()
            ->where('opportunity_id', $opportunity->id)
            ->get();

        foreach ($docs as $doc) {
            $disk    = Storage::disk($doc->disk);
            $newPath = $doc->path;
            $newThumb = $doc->thumbnail_path;
            $changed  = false;

            if ($doc->path && str_starts_with($doc->path, $oldPrefix . '/')) {
                $newPath = $newPrefix . '/' . basename($doc->path);
                if ($disk->exists($doc->path)) {
                    $disk->move($doc->path, $newPath);
                } else {
                    Log::warning("OpportunityFolderService: file not found on disk, updating DB path only: {$doc->path}");
                }
                $changed = true;
            }

            if ($doc->thumbnail_path && str_starts_with($doc->thumbnail_path, $oldPrefix . '/')) {
                $newThumb = $newPrefix . '/' . basename($doc->thumbnail_path);
                if ($disk->exists($doc->thumbnail_path)) {
                    $disk->move($doc->thumbnail_path, $newThumb);
                }
                $changed = true;
            }

            if ($changed) {
                OpportunityDocument::withoutTimestamps(fn () => $doc->update([
                    'path'           => $newPath,
                    'thumbnail_path' => $newThumb,
                ]));
            }
        }

        Log::info("OpportunityFolderService: renamed folder for opportunity #{$opportunity->id}: {$oldFolderName} → {$newFolderName}");

        return true;
    }
}
