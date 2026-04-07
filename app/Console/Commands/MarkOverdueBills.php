<?php

namespace App\Console\Commands;

use App\Models\Bill;
use Illuminate\Console\Command;

class MarkOverdueBills extends Command
{
    protected $signature = 'bills:mark-overdue';

    protected $description = 'Flip pending bills past their due date to overdue status';

    public function handle(): void
    {
        $count = Bill::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} bill(s) as overdue.");
    }
}
