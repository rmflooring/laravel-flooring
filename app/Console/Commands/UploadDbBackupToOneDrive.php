<?php

namespace App\Console\Commands;

use App\Services\GraphOneDriveService;
use Illuminate\Console\Command;

class UploadDbBackupToOneDrive extends Command
{
    protected $signature = 'backup:upload-db {file : Absolute path to the backup file}';

    protected $description = 'Upload a database backup file to OneDrive (FloorManager/db-backups/)';

    public function handle(GraphOneDriveService $oneDrive): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $filename = basename($file);
        $size     = round(filesize($file) / 1024 / 1024, 1);

        $this->info("Uploading {$filename} ({$size}MB) to OneDrive...");

        $success = $oneDrive->uploadLocalFile($file, 'db-backups');

        if ($success) {
            $this->info("Done — FloorManager/db-backups/{$filename}");
            return self::SUCCESS;
        }

        $this->error('Upload failed — check Laravel logs for details.');
        return self::FAILURE;
    }
}
