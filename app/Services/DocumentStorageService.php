<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    /**
     * Return the disk name to use for document storage.
     * For S3 and SFTP, registers a named 'documents' disk at runtime.
     */
    public static function disk(): string
    {
        $driver = Setting::get('storage_driver', 'local');

        return match ($driver) {
            's3'   => static::ensureS3(),
            'sftp' => static::ensureSftp(),
            default => 'public',
        };
    }

    /**
     * Generate a public URL for a stored file.
     * Falls back to a configured base URL for SFTP disks where Storage::url() is unavailable.
     */
    public static function url(string $disk, string $path): string
    {
        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable) {
            $base = rtrim(Setting::get('storage_sftp_url', ''), '/');
            return $base . '/' . ltrim($path, '/');
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function ensureS3(): string
    {
        $secret = Setting::get('storage_s3_secret', '');
        try { $secret = decrypt($secret); } catch (\Throwable) {}

        config(['filesystems.disks.documents' => [
            'driver'                  => 's3',
            'key'                     => Setting::get('storage_s3_key', ''),
            'secret'                  => $secret,
            'region'                  => Setting::get('storage_s3_region', 'us-east-1'),
            'bucket'                  => Setting::get('storage_s3_bucket', ''),
            'url'                     => Setting::get('storage_s3_url') ?: null,
            'endpoint'                => Setting::get('storage_s3_endpoint') ?: null,
            'use_path_style_endpoint' => Setting::get('storage_s3_path_style', '0') === '1',
            'visibility'              => 'public',
            'throw'                   => false,
        ]]);

        return 'documents';
    }

    private static function ensureSftp(): string
    {
        $password = Setting::get('storage_sftp_password', '');
        try { $password = decrypt($password); } catch (\Throwable) {}

        config(['filesystems.disks.documents' => [
            'driver'   => 'sftp',
            'host'     => Setting::get('storage_sftp_host', ''),
            'username' => Setting::get('storage_sftp_username', ''),
            'password' => $password,
            'port'     => (int) Setting::get('storage_sftp_port', 22),
            'root'     => Setting::get('storage_sftp_root', '/'),
            'url'      => Setting::get('storage_sftp_url') ?: null,
            'throw'    => false,
        ]]);

        return 'documents';
    }
}
