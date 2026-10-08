<?php

namespace App\Console\Commands;

use App\Services\WebOrderService;
use Illuminate\Console\Command;

class SendWebOrderReminders extends Command
{
    protected $signature = 'web-orders:remind';
    protected $description = 'Remind customers whose web order has been ready for pickup longer than the hold period';

    public function handle(WebOrderService $service): int
    {
        $this->info('Pickup reminders sent: ' . $service->sendPickupReminders());

        return self::SUCCESS;
    }
}
