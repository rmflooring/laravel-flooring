<?php

namespace App\Console\Commands;

use App\Jobs\MirrorFileToOneDrive;
use App\Models\OpportunityDocument;
use Illuminate\Console\Command;

class MirrorExistingFilesToOneDrive extends Command
{
    protected $signature   = 'app:mirror-to-onedrive
                              {--dry-run : Preview jobs that would be queued without dispatching}';

    protected $description = 'Queue OneDrive mirror jobs for all existing opportunity documents';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no jobs will be dispatched.');
        }

        $total     = 0;
        $dispatched = 0;

        OpportunityDocument::withTrashed()
            ->whereNotNull('path')
            ->chunkById(100, function ($docs) use ($dryRun, &$total, &$dispatched) {
                foreach ($docs as $doc) {
                    $total++;

                    if (! $dryRun) {
                        MirrorFileToOneDrive::dispatch($doc->disk, $doc->path);
                        $dispatched++;
                    }

                    if ($doc->thumbnail_path) {
                        $total++;
                        if (! $dryRun) {
                            MirrorFileToOneDrive::dispatch($doc->disk, $doc->thumbnail_path);
                            $dispatched++;
                        }
                    }
                }
            });

        if ($dryRun) {
            $this->info("Would queue {$total} file(s) for mirroring.");
            $this->warn('DRY RUN complete — run without --dry-run to dispatch jobs.');
        } else {
            $this->info("Queued {$dispatched} mirror job(s). The queue worker will process them in the background.");
        }

        return 0;
    }
}
