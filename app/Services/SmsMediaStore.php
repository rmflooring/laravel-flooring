<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Stores SMS-portal MMS pictures on the private local disk — both ones customers text in
 * (downloaded from VoIP.ms, whose media links aren't ours to rely on long-term) and ones
 * staff attach to send. Each stored item is a small array saved on SmsMessage::media.
 */
class SmsMediaStore
{
    private const DISK = 'local';

    private const MAX_DOWNLOAD_BYTES = 20 * 1024 * 1024;

    // Carriers commonly cap a whole MMS at ~1 MB, so each outgoing picture (up to 3) is
    // re-encoded well under that.
    private const SEND_MAX_DIMENSION = 1600;

    private const SEND_TARGET_BYTES = 300 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'video/mp4'  => 'mp4',
        'video/3gpp' => '3gp',
        'video/quicktime' => 'mov',
        'audio/amr'  => 'amr',
        'audio/mpeg' => 'mp3',
    ];

    /**
     * Download a picture a customer texted in. Only VoIP.ms's own media host is fetched,
     * so the webhook can't be used to make the server request arbitrary URLs.
     */
    public function storeFromUrl(string $url): ?array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! in_array($host, ['voip.ms', 'www.voip.ms'], true)) {
            Log::warning('[SMS Media] Refused to download from unexpected host', ['url' => $url]);
            return null;
        }

        try {
            $response = Http::timeout(30)->get($url);
        } catch (\Throwable $e) {
            Log::warning('[SMS Media] Download failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }

        $contents = $response->body();
        if (! $response->successful() || $contents === '' || strlen($contents) > self::MAX_DOWNLOAD_BYTES) {
            Log::warning('[SMS Media] Download unusable', ['url' => $url, 'status' => $response->status(), 'bytes' => strlen($contents)]);
            return null;
        }

        $mime = strtolower(trim(explode(';', $response->header('Content-Type'))[0]));
        if (! isset(self::EXTENSIONS[$mime])) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
        }

        return $this->put($contents, $mime);
    }

    /**
     * Store a picture staff attached to an outgoing message, shrunk to an MMS-friendly
     * JPEG. GIFs stay as-is when already small enough so they keep their animation.
     */
    public function storeUpload(UploadedFile $file): array
    {
        $mime = $file->getMimeType();

        if ($mime === 'image/gif' && $file->getSize() <= self::SEND_TARGET_BYTES) {
            return $this->put($file->get(), $mime);
        }

        $image = (new ImageManager(new Driver()))->decodePath($file->getRealPath());
        $image->scaleDown(width: self::SEND_MAX_DIMENSION, height: self::SEND_MAX_DIMENSION);

        $contents = (string) $image->encodeUsingMediaType('image/jpeg', quality: 80);
        if (strlen($contents) > self::SEND_TARGET_BYTES) {
            $contents = (string) $image->encodeUsingMediaType('image/jpeg', quality: 60);
        }

        return $this->put($contents, 'image/jpeg');
    }

    public function contents(array $item): ?string
    {
        return Storage::disk(self::DISK)->get($item['path']);
    }

    /**
     * @param  array<int, array>  $items
     */
    public function delete(array $items): void
    {
        $paths = array_values(array_filter(array_column($items, 'path')));
        if ($paths) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISK);
    }

    private function put(string $contents, string $mime): array
    {
        $ext  = self::EXTENSIONS[$mime] ?? 'bin';
        $path = 'sms-media/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $ext;

        Storage::disk(self::DISK)->put($path, $contents);

        return ['path' => $path, 'mime' => $mime, 'size' => strlen($contents)];
    }
}
