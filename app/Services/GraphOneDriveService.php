<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GraphOneDriveService
{
    private const GRAPH_BASE = 'https://graph.microsoft.com/v1.0';

    // OneDrive user to mirror files into
    private const ONEDRIVE_USER = 'richard@rmflooring.ca';

    // Root folder inside OneDrive where all files are stored
    private const ONEDRIVE_ROOT = 'FloorManager';

    /**
     * Obtain an app-level access token (reuses same pattern as GraphMailService).
     */
    public function getAppToken(): string
    {
        $tenantId     = config('services.microsoft.tenant_id');
        $clientId     = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (! $response->successful()) {
            Log::error('[OneDrive] Failed to obtain app token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('GraphOneDriveService: could not obtain app access token.');
        }

        return $response->json('access_token');
    }

    /**
     * Upload a file to OneDrive, mirroring the same relative path used on the primary disk.
     *
     * @param  string  $disk      The source disk (e.g. 'public')
     * @param  string  $path      Relative path on the source disk (e.g. 'opportunities/Sandra_Cokinass - 26-0001/file.jpg')
     */
    public function mirror(string $disk, string $path): bool
    {
        try {
            $contents = Storage::disk($disk)->get($path);

            if ($contents === null) {
                Log::warning('[OneDrive] Source file not found, skipping mirror', ['path' => $path]);
                return false;
            }

            $token       = $this->getAppToken();
            $oneDrivePath = self::ONEDRIVE_ROOT . '/' . $path;

            // Graph API simple upload (up to 4MB) — use upload session for larger files
            $sizeBytes = strlen($contents);

            if ($sizeBytes <= 4 * 1024 * 1024) {
                return $this->simpleUpload($token, $oneDrivePath, $contents);
            }

            return $this->uploadSession($token, $oneDrivePath, $contents, $sizeBytes);

        } catch (\Throwable $e) {
            Log::error('[OneDrive] Mirror failed', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Rename a folder on OneDrive by patching its name in place.
     *
     * @param  string  $oldRelativePath  e.g. 'opportunities/OldName - 26-0001'
     * @param  string  $newFolderName    e.g. 'NewName - 26-0001'  (just the final segment)
     */
    public function renameFolder(string $oldRelativePath, string $newFolderName): bool
    {
        try {
            $token = $this->getAppToken();

            $oneDrivePath = self::ONEDRIVE_ROOT . '/' . $oldRelativePath;
            $encodedPath  = implode('/', array_map('rawurlencode', explode('/', $oneDrivePath)));
            $url          = self::GRAPH_BASE . '/users/' . self::ONEDRIVE_USER . '/drive/root:/' . $encodedPath;

            $response = Http::withToken($token)->patch($url, [
                'name' => $newFolderName,
            ]);

            // 404 means the folder never existed on OneDrive (no files uploaded yet) — not an error
            if ($response->status() === 404) {
                return true;
            }

            if (! $response->successful()) {
                Log::error('[OneDrive] Folder rename failed', [
                    'old_path'   => $oldRelativePath,
                    'new_name'   => $newFolderName,
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                ]);
                return false;
            }

            Log::info('[OneDrive] Folder renamed', [
                'old_path' => $oldRelativePath,
                'new_name' => $newFolderName,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('[OneDrive] Folder rename exception', [
                'old_path' => $oldRelativePath,
                'new_name' => $newFolderName,
                'message'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Upload a local file (by absolute path) to OneDrive under FloorManager/db-backups/.
     *
     * @param  string  $localPath     Absolute path on disk, e.g. /mnt/nas_storage/backups/db/fm_laravel_2026-04-20.sql.gz
     * @param  string  $oneDriveFolder  Folder inside FloorManager, e.g. 'db-backups'
     */
    public function uploadLocalFile(string $localPath, string $oneDriveFolder = 'db-backups'): bool
    {
        try {
            if (! file_exists($localPath)) {
                Log::warning('[OneDrive] uploadLocalFile: file not found', ['path' => $localPath]);
                return false;
            }

            $contents    = file_get_contents($localPath);
            $sizeBytes   = strlen($contents);
            $filename    = basename($localPath);
            $token       = $this->getAppToken();
            $oneDrivePath = self::ONEDRIVE_ROOT . '/' . $oneDriveFolder . '/' . $filename;

            if ($sizeBytes <= 4 * 1024 * 1024) {
                return $this->simpleUpload($token, $oneDrivePath, $contents);
            }

            return $this->uploadSession($token, $oneDrivePath, $contents, $sizeBytes);

        } catch (\Throwable $e) {
            Log::error('[OneDrive] uploadLocalFile failed', [
                'path'    => $localPath,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Simple upload for files up to 4MB.
     */
    private function simpleUpload(string $token, string $oneDrivePath, string $contents): bool
    {
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $oneDrivePath)));
        $url         = self::GRAPH_BASE . '/users/' . self::ONEDRIVE_USER . '/drive/root:/' . $encodedPath . ':/content';

        $response = Http::withToken($token)
            ->withBody($contents, 'application/octet-stream')
            ->put($url);

        if (! $response->successful()) {
            Log::error('[OneDrive] Simple upload failed', [
                'path'   => $oneDrivePath,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Upload session for files larger than 4MB.
     */
    private function uploadSession(string $token, string $oneDrivePath, string $contents, int $sizeBytes): bool
    {
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $oneDrivePath)));
        $url         = self::GRAPH_BASE . '/users/' . self::ONEDRIVE_USER . '/drive/root:/' . $encodedPath . ':/createUploadSession';

        // Create upload session
        $sessionResponse = Http::withToken($token)->post($url, [
            'item' => ['@microsoft.graph.conflictBehavior' => 'replace'],
        ]);

        if (! $sessionResponse->successful()) {
            Log::error('[OneDrive] Failed to create upload session', [
                'path'   => $oneDrivePath,
                'status' => $sessionResponse->status(),
                'body'   => $sessionResponse->body(),
            ]);
            return false;
        }

        $uploadUrl = $sessionResponse->json('uploadUrl');

        // Upload in 4MB chunks
        $chunkSize = 4 * 1024 * 1024;
        $offset    = 0;

        while ($offset < $sizeBytes) {
            $chunk     = substr($contents, $offset, $chunkSize);
            $chunkLen  = strlen($chunk);
            $rangeEnd  = $offset + $chunkLen - 1;

            $chunkResponse = Http::withHeaders([
                'Content-Length' => $chunkLen,
                'Content-Range'  => "bytes {$offset}-{$rangeEnd}/{$sizeBytes}",
            ])->withBody($chunk, 'application/octet-stream')->put($uploadUrl);

            if (! $chunkResponse->successful() && $chunkResponse->status() !== 202) {
                Log::error('[OneDrive] Chunk upload failed', [
                    'path'   => $oneDrivePath,
                    'offset' => $offset,
                    'status' => $chunkResponse->status(),
                ]);
                return false;
            }

            $offset += $chunkLen;
        }

        return true;
    }
}
