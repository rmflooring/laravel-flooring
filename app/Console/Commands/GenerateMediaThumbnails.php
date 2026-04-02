<?php

namespace App\Console\Commands;

use App\Models\OpportunityDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class GenerateMediaThumbnails extends Command
{
    protected $signature = 'media:generate-thumbnails
                            {--force : Regenerate thumbnails even if one already exists}';

    protected $description = 'Generate thumbnails for existing media images that do not have one';

    public function handle(): int
    {
        $query = OpportunityDocument::withTrashed()
            ->where('category', 'media')
            ->whereNotNull('mime_type')
            ->where('mime_type', 'not like', 'video/%')
            ->where('mime_type', '<>', 'image/svg+xml');

        if (! $this->option('force')) {
            $query->whereNull('thumbnail_path');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No images to process.');
            return 0;
        }

        $this->info("Processing {$total} image(s)...");

        $manager   = new ImageManager(new Driver());
        $generated = 0;
        $failed    = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(50, function ($docs) use ($manager, &$generated, &$failed, $bar) {
            foreach ($docs as $doc) {
                try {
                    $contents = Storage::disk($doc->disk)->get($doc->path);

                    if ($contents === null) {
                        $this->newLine();
                        $this->warn("  Skipped (file not found): {$doc->path}");
                        $failed++;
                        $bar->advance();
                        continue;
                    }

                    $image = $manager->read($contents);
                    $image->scaleDown(width: 600);
                    $thumbContents = (string) $image->toJpeg(quality: 80);

                    $thumbPath = dirname($doc->path)
                        . '/thumb_' . pathinfo(basename($doc->path), PATHINFO_FILENAME) . '.jpg';

                    Storage::disk($doc->disk)->put($thumbPath, $thumbContents);

                    $doc->withoutTimestamps()->update(['thumbnail_path' => $thumbPath]);

                    $generated++;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("  Failed [{$doc->id}] {$doc->original_name}: {$e->getMessage()}");
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Generated: {$generated}, Failed/Skipped: {$failed}");

        return 0;
    }
}
