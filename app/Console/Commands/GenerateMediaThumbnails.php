<?php

namespace App\Console\Commands;

use App\Models\OpportunityDocument;
use App\Services\MediaThumbnailService;
use Illuminate\Console\Command;

class GenerateMediaThumbnails extends Command
{
    protected $signature = 'media:generate-thumbnails
                            {--force : Regenerate thumbnails even if they already exist}
                            {--retry-failed : Also retry images that failed before (e.g. HEIC)}
                            {--limit=0 : Stop after this many images (0 = no limit)}';

    protected $description = 'Generate gallery thumbnails (~600px and ~300px) for media images that are missing them';

    public function handle(MediaThumbnailService $thumbnails): int
    {
        $query = OpportunityDocument::withTrashed()
            ->where('category', 'media')
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '<>', 'image/svg+xml');

        if (! $this->option('force')) {
            $query->where(fn ($q) => $q->whereNull('thumbnail_path')->orWhereNull('thumbnail_sm_path'));
        }
        if (! $this->option('force') && ! $this->option('retry-failed')) {
            $query->whereNull('thumbnail_failed_at');
        }

        $limit = (int) $this->option('limit');
        $total = $query->count();
        if ($limit > 0) {
            $total = min($total, $limit);
        }

        if ($total === 0) {
            $this->info('No images to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$total} image(s)...");

        $generated = 0;
        $failed    = 0;
        $bar       = $this->output->createProgressBar($total);
        $bar->start();

        // Newest first, so active jobs get their thumbnails soonest
        foreach ($query->lazyByIdDesc(50, 'id')->take($total) as $doc) {
            if ($thumbnails->generate($doc)) {
                $generated++;
            } else {
                $failed++;
                $this->newLine();
                $this->warn("  Failed [{$doc->id}] {$doc->original_name}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Generated: {$generated}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
