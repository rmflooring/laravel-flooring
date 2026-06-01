<?php

namespace App\Console\Commands;

use App\Mail\SignatureReminderMail;
use App\Models\DocumentSigningRequest;
use App\Services\GraphMailService;
use Illuminate\Console\Command;

class SendSigningReminders extends Command
{
    protected $signature = 'signing:send-reminders';

    protected $description = 'Send reminder emails for pending signing requests at 3, 7, and 9 days';

    public function handle(): void
    {
        $requests = DocumentSigningRequest::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->where(function ($q) {
                $q->where(function ($q) {
                        $q->where('reminder_count', 0)
                          ->where('sent_at', '<=', now()->subDays(3));
                    })
                    ->orWhere(function ($q) {
                        $q->where('reminder_count', 1)
                          ->where('sent_at', '<=', now()->subDays(7));
                    })
                    ->orWhere(function ($q) {
                        $q->where('reminder_count', 2)
                          ->where('sent_at', '<=', now()->subDays(9));
                    });
            })
            ->get();

        $count  = 0;
        $mailer = app(GraphMailService::class);

        foreach ($requests as $signingRequest) {
            (new SignatureReminderMail($signingRequest))->send();

            $documentLabel  = $signingRequest->document_type === 'flooring_selection'
                ? 'Flooring Selection'
                : 'Work Authorization';
            $reminderNumber = $signingRequest->reminder_count + 1;

            $mailer->send(
                to:      'richard@rmflooring.ca',
                subject: "Reminder #{$reminderNumber} sent — {$documentLabel} awaiting signature",
                body:    "A reminder was sent to {$signingRequest->client_name} <{$signingRequest->client_email}> for their {$documentLabel} document.\n\nDocument ID: {$signingRequest->uuid}\nReminder #: {$reminderNumber}",
                type:    'signing_reminder_admin',
            );

            $signingRequest->update([
                'reminder_sent_at' => now(),
                'reminder_count'   => $signingRequest->reminder_count + 1,
            ]);

            $count++;
        }

        $this->info("Sent {$count} signing reminder(s).");
    }
}
