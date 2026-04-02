<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateOpportunityFolders extends Command
{
    protected $signature   = 'app:migrate-opportunity-folders
                              {--dry-run : Preview changes without moving any files}';

    protected $description = 'Rename opportunity storage folders from {id} to {JobSiteName} - {job_no}';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be moved.');
        }

        // Get all opportunity IDs that have documents
        $opportunityIds = OpportunityDocument::withTrashed()
            ->distinct()
            ->pluck('opportunity_id');

        if ($opportunityIds->isEmpty()) {
            $this->info('No documents found.');
            return 0;
        }

        $opportunities = Opportunity::with('jobSiteCustomer')
            ->whereIn('id', $opportunityIds)
            ->get();

        $moved   = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($opportunities as $opportunity) {
            $oldPrefix = "opportunities/{$opportunity->id}";
            $newPrefix = "opportunities/{$opportunity->storageFolderName()}";

            if ($oldPrefix === $newPrefix) {
                $skipped++;
                continue;
            }

            $this->line("  <info>[{$opportunity->id}]</info> {$oldPrefix} → {$newPrefix}");

            $docs = OpportunityDocument::withTrashed()
                ->where('opportunity_id', $opportunity->id)
                ->get();

            foreach ($docs as $doc) {
                $disk = Storage::disk($doc->disk);

                $newPath      = $doc->path;
                $newThumbPath = $doc->thumbnail_path;
                $changed      = false;

                // Move main file
                if ($doc->path && str_starts_with($doc->path, $oldPrefix . '/')) {
                    $newPath = $newPrefix . '/' . basename($doc->path);
                    if (! $dryRun) {
                        if ($disk->exists($doc->path)) {
                            $disk->move($doc->path, $newPath);
                        } else {
                            $this->warn("    File not found on disk, updating DB only: {$doc->path}");
                        }
                    }
                    $changed = true;
                }

                // Move thumbnail
                if ($doc->thumbnail_path && str_starts_with($doc->thumbnail_path, $oldPrefix . '/')) {
                    $newThumbPath = $newPrefix . '/' . basename($doc->thumbnail_path);
                    if (! $dryRun) {
                        if ($disk->exists($doc->thumbnail_path)) {
                            $disk->move($doc->thumbnail_path, $newThumbPath);
                        }
                    }
                    $changed = true;
                }

                if ($changed && ! $dryRun) {
                    $doc->withoutTimestamps()->update([
                        'path'           => $newPath,
                        'thumbnail_path' => $newThumbPath,
                    ]);
                }

                $moved++;
            }
        }

        $this->newLine();
        $this->info("Done. Records processed: {$moved}, Opportunities skipped (already correct): {$skipped}, Failed: {$failed}");

        if ($dryRun) {
            $this->warn('DRY RUN complete — run without --dry-run to apply changes.');
        }

        return 0;
    }
}
