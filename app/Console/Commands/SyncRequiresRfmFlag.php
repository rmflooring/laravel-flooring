<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use Illuminate\Console\Command;

class SyncRequiresRfmFlag extends Command
{
    protected $signature = 'opportunities:sync-requires-rfm';
    protected $description = 'Clear the Requires RFM flag on any opportunity that already has an RFM booked.';

    public function handle(): void
    {
        $opportunities = Opportunity::where('requires_rfm', true)
            ->whereHas('rfms')
            ->get(['id']);

        foreach ($opportunities as $opportunity) {
            $opportunity->update(['requires_rfm' => false]);
        }

        $this->info("Done — {$opportunities->count()} opportunity(ies) cleared.");
    }
}
