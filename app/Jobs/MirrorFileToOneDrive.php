<?php

namespace App\Jobs;

use App\Services\GraphOneDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MirrorFileToOneDrive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
    ) {}

    public function handle(GraphOneDriveService $service): void
    {
        $success = $service->mirror($this->disk, $this->path);

        if ($success) {
            Log::info('[OneDrive] Mirrored file', ['path' => $this->path]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[OneDrive] MirrorFileToOneDrive job failed permanently', [
            'path'    => $this->path,
            'message' => $e->getMessage(),
        ]);
    }
}
