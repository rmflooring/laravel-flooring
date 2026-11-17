<?php

namespace App\Services;

use App\Models\OpportunityDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Makes the gallery thumbnails for an uploaded image, next to the original file:
 *   thumb_<name>.jpg    ~600px wide  (thumbnail_path)    — medium / large gallery views
 *   thumbsm_<name>.jpg  ~300px wide  (thumbnail_sm_path) — compact gallery view
 *
 * Every upload path that creates an image document should call generate() so the
 * gallery never has to fall back to downloading the full-size photo.
 */
class MediaThumbnailService
{
    public const MEDIUM_WIDTH = 600;
    public const SMALL_WIDTH  = 300;

    public static function isThumbable(?string $mime, ?string $extension = null): bool
    {
        $mime = strtolower((string) $mime);
        $ext  = strtolower((string) $extension);

        return str_starts_with($mime, 'image/')
            && $mime !== 'image/svg+xml'
            && $ext !== 'svg';
    }

    /**
     * Generate both thumbnails and save their paths on the document.
     *
     * @param  string|null  $sourceFile  Local path of the original (e.g. the upload's temp file),
     *                                   to avoid reading it back from the storage disk.
     */
    public function generate(OpportunityDocument $doc, ?string $sourceFile = null): bool
    {
        if (! self::isThumbable($doc->mime_type, $doc->extension) || ! $doc->disk || ! $doc->path) {
            return false;
        }

        try {
            $manager = new ImageManager(new Driver());

            if ($sourceFile && is_readable($sourceFile)) {
                $image = $manager->decodePath($sourceFile);
            } else {
                $contents = Storage::disk($doc->disk)->get($doc->path);
                if ($contents === null) {
                    throw new \RuntimeException('Original file not found on disk.');
                }
                $image = $manager->decodeBinary($contents);
                unset($contents);
            }

            $dir  = dirname($doc->path);
            $stem = pathinfo(basename($doc->path), PATHINFO_FILENAME);
            $disk = Storage::disk($doc->disk);

            $mediumPath = "{$dir}/thumb_{$stem}.jpg";
            $image->scaleDown(width: self::MEDIUM_WIDTH);
            $disk->put($mediumPath, (string) $image->encodeUsingMediaType('image/jpeg', quality: 80));
            DocumentStorageService::forceOpenPermissions($doc->disk, $mediumPath);

            $smallPath = "{$dir}/thumbsm_{$stem}.jpg";
            $image->scaleDown(width: self::SMALL_WIDTH);
            $disk->put($smallPath, (string) $image->encodeUsingMediaType('image/jpeg', quality: 75));
            DocumentStorageService::forceOpenPermissions($doc->disk, $smallPath);

            OpportunityDocument::withoutTimestamps(fn () => $doc->update([
                'thumbnail_path'      => $mediumPath,
                'thumbnail_sm_path'   => $smallPath,
                'thumbnail_failed_at' => null,
            ]));

            return true;
        } catch (\Throwable $e) {
            Log::warning('[media] thumbnail generation failed', [
                'document_id' => $doc->id,
                'path'        => $doc->path,
                'message'     => $e->getMessage(),
            ]);

            OpportunityDocument::withoutTimestamps(fn () => $doc->update(['thumbnail_failed_at' => now()]));

            return false;
        }
    }

    /** Remove a document's thumbnail files from its disk (originals are handled by the caller). */
    public static function deleteFiles(OpportunityDocument $doc): void
    {
        if (! $doc->disk) {
            return;
        }
        foreach ([$doc->thumbnail_path, $doc->thumbnail_sm_path] as $path) {
            if ($path) {
                Storage::disk($doc->disk)->delete($path);
            }
        }
    }
}
