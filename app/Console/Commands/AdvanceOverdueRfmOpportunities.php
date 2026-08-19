<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use Illuminate\Console\Command;

class AdvanceOverdueRfmOpportunities extends Command
{
    protected $signature = 'opportunities:advance-overdue-rfm';
    protected $description = 'Move opportunities from Awaiting Site Measure to In Progress once their RFM visit date has passed.';

    public function handle(): void
    {
        $opportunities = Opportunity::where('status', 'Awaiting Site Measure')
            ->with('rfms') // Opportunity::rfms() is already ordered by scheduled_at desc
            ->get();

        $advanced = 0;

        foreach ($opportunities as $opportunity) {
            $latestActive = $opportunity->rfms->first(fn ($rfm) => $rfm->status !== 'cancelled');

            if ($latestActive && $latestActive->scheduled_at?->isPast()) {
                $opportunity->update(['status' => 'In Progress']);
                $advanced++;
                $this->info("Opportunity #{$opportunity->id} advanced to In Progress (RFM #{$latestActive->id} was {$latestActive->scheduled_at}).");
            }
        }

        $this->info("Done — {$advanced} opportunity(ies) advanced.");
    }
}
