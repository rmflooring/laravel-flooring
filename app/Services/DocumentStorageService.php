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
     * Force a just-written file (and its containing folder) to be fully
     * read/write/execute for owner+group+other, on the local NAS-backed disk only.
     *
     * Needed because this disk (storage/app/public) is a symlink onto an NFS mount
     * (see Context/context_storage.md — NFS root_squash means writes from this app
     * land on the NAS under a fixed identity, not the real uploading user), and the
     * app's own configured 0777/0664 permissions (config/filesystems.php) don't
     * reliably survive the write — observed live 2026-09-18: newly-created folders
     * landing at 0755 (exactly 0777 masked by a 022 umask) instead of 0777, which
     * then blocks editing/overwriting those files over SMB from a Windows PC even
     * though the app itself can still read/write them fine over NFS. Rather than
     * chase the exact umask/Flysystem interaction, this explicitly re-asserts 0777
     * right after every write so the end state is guaranteed regardless of cause.
     *
     * No-ops for S3/SFTP disks, which have no POSIX permission model to set.
     */
    public static function forceOpenPermissions(string $disk, string $path): void
    {
        if ($disk !== 'public') {
            return;
        }

        try {
            $absolute = Storage::disk($disk)->path($path);
            @chmod($absolute, 0777);

            $dir = dirname($absolute);
            if ($dir !== '.' && is_dir($dir)) {
                @chmod($dir, 0777);
            }
        } catch (\Throwable) {
            // Best-effort — a permission fixup failing should never break the
            // actual upload that already succeeded.
        }
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
