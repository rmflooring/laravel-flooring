<?php

namespace App\Console\Commands;

use App\Models\ShowroomVisit;
use App\Services\ShowroomVisitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendShowroomVisitReminders extends Command
{
    protected $signature = 'showroom:send-reminders
                            {type : day-before | morning}
                            {--dry-run : List who would be reminded without sending}';

    protected $description = 'Send customer email + SMS reminders for upcoming showroom visits (day-before at 4pm, morning-of at 8am).';

    // Don't remind someone who was just sent their booking confirmation.
    private const RECENT_CONFIRMATION_HOURS = 6;

    public function handle(ShowroomVisitService $service): int
    {
        $type = $this->argument('type');

        [$column, $kind, $from, $to] = match ($type) {
            'day-before' => ['reminder_day_before_sent_at', 'reminder_day_before', Carbon::tomorrow()->startOfDay(), Carbon::tomorrow()->endOfDay()],
            'morning'    => ['reminder_morning_sent_at', 'reminder_morning', now(), Carbon::today()->endOfDay()],
            default      => [null, null, null, null],
        };

        if (! $column) {
            $this->error('Type must be "day-before" or "morning".');
            return self::INVALID;
        }

        // Only plain "scheduled" visits — if the customer has a pending change
        // request, reminding them of the old time would just confuse things.
        $visits = ShowroomVisit::with(['customer', 'host', 'opportunity'])
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNull($column)
            ->orderBy('scheduled_at')
            ->get();

        $sent = 0;

        foreach ($visits as $visit) {
            $label = "#{$visit->id} {$visit->contact_name} @ {$visit->scheduled_at->format('M j g:ia')}";

            if ($visit->confirmation_sent_at && $visit->confirmation_sent_at->gt(now()->subHours(self::RECENT_CONFIRMATION_HOURS))) {
                $this->line("  skip (confirmed recently) {$label}");
                if (! $this->option('dry-run')) {
                    $visit->forceFill([$column => now()])->saveQuietly();
                }
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  would remind {$label}");
                continue;
            }

            try {
                $result = $service->notifyCustomer($visit, $kind);
                $visit->forceFill([$column => now()])->saveQuietly();

                $channels = implode('+', array_keys(array_filter($result))) ?: 'nothing sent';
                $this->info("  reminded {$label} ({$channels})");
                if ($result['email'] || $result['sms']) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::error('[ShowroomVisit] Reminder failed', ['visit_id' => $visit->id, 'type' => $type, 'error' => $e->getMessage()]);
                $this->error("  FAILED {$label}: {$e->getMessage()}");
            }
        }

        $this->info("Showroom {$type} reminders: {$sent} sent, {$visits->count()} checked.");

        return self::SUCCESS;
    }
}
