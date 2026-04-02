<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\GraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckNasHealth extends Command
{
    protected $signature   = 'nas:check-health';
    protected $description = 'Check NAS storage connectivity and alert admin if offline';

    private const ALERT_EMAIL   = 'richard@rmflooring.ca';
    private const TEST_FILE     = '.nas-health-check.txt';

    public function handle(): int
    {
        $wasOnline = Setting::get('nas_status', 'online') === 'online';
        $isOnline  = $this->checkNas();
        $now       = now()->toDateTimeString();

        Setting::set('nas_status', $isOnline ? 'online' : 'offline');
        Setting::set('nas_last_checked', $now);

        // Status changed — send alert
        if ($wasOnline && ! $isOnline) {
            $this->sendAlert(
                '⚠️ NAS Storage Offline — Floor Manager',
                "The NAS storage at /mnt/nas_storage is unreachable as of {$now}.\n\n" .
                "File uploads will fail until the NAS is back online.\n\n" .
                "Check that the WD My Cloud (192.168.1.143) is powered on and connected to the network."
            );
            $this->warn('NAS is OFFLINE — alert sent.');
        } elseif (! $wasOnline && $isOnline) {
            $this->sendAlert(
                '✅ NAS Storage Back Online — Floor Manager',
                "The NAS storage at /mnt/nas_storage is back online as of {$now}.\n\n" .
                "File uploads are working normally again."
            );
            $this->info('NAS is back ONLINE — all clear sent.');
        } else {
            $this->info('NAS status: ' . ($isOnline ? 'online' : 'offline') . ' (no change)');
        }

        return 0;
    }

    private function checkNas(): bool
    {
        try {
            $testContent = 'FM NAS health check — ' . now()->toDateTimeString();

            Storage::disk('public')->put(self::TEST_FILE, $testContent);
            $read = Storage::disk('public')->get(self::TEST_FILE);
            Storage::disk('public')->delete(self::TEST_FILE);

            return $read === $testContent;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sendAlert(string $subject, string $body): void
    {
        try {
            app(GraphMailService::class)->send(
                self::ALERT_EMAIL,
                $subject,
                $body,
                'nas_alert'
            );
        } catch (\Throwable $e) {
            $this->error('Failed to send alert email: ' . $e->getMessage());
        }
    }
}
