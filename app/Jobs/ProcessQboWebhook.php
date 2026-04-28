<?php

namespace App\Jobs;

use App\Models\QboConnection;
use App\Services\QboSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQboWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private array $payload) {}

    public function handle(QboSyncService $sync): void
    {
        $realmId = QboConnection::first()?->realm_id;

        foreach ($this->payload['eventNotifications'] as $notification) {
            // Only process notifications for our company
            if ($notification['realmId'] !== $realmId) {
                continue;
            }

            $entities = $notification['dataChangeEvent']['entities'] ?? [];

            foreach ($entities as $entity) {
                $name      = $entity['name'] ?? '';
                $qboId     = $entity['id'] ?? '';
                $operation = $entity['operation'] ?? '';

                if (! $qboId) {
                    continue;
                }

                Log::info("[QBO Webhook] {$operation} {$name} #{$qboId}");

                match ($name) {
                    'Bill'    => $sync->handleBillUpdate($qboId, $operation),
                    'Invoice' => $sync->handleInvoiceUpdate($qboId, $operation),
                    default   => null,
                };
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[QBO Webhook] Job failed', ['error' => $e->getMessage()]);
    }
}
