<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Models\SmsMessage;
use App\Services\VoipMsSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Follows up a VoIP.ms send that timed out ("unconfirmed"). VoIP.ms usually delivers
 * those anyway, so rather than resending (and double-texting the customer) this checks
 * the line's sent history and settles the message as sent — or, if VoIP.ms still has
 * no record after ~25 minutes, as not delivered so staff know to resend.
 */
class VerifyVoipMsSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Minutes to wait before each re-check, after the first one. */
    private const RECHECK_DELAYS = [2, 5, 15];

    public int $tries = 4;

    public function __construct(public int $smsLogId, public ?int $smsMessageId = null)
    {
    }

    public function handle(VoipMsSmsService $voipMs): void
    {
        $log = SmsLog::find($this->smsLogId);

        if (! $log || $log->status !== 'unconfirmed') {
            return; // already settled (e.g. by hand)
        }

        $sent = $voipMs->wasSent($log);

        if ($sent === true) {
            $log->update(['status' => 'sent', 'error' => null, 'sent_at' => $log->created_at]);
            $this->message()?->update(['unconfirmed' => false, 'failed' => false]);
            Log::info('[VoIP.ms] Unconfirmed send verified as sent', ['sms_log_id' => $log->id]);
            return;
        }

        $delay = self::RECHECK_DELAYS[$this->attempts() - 1] ?? null;

        if ($delay !== null) {
            $this->release($delay * 60);
            return;
        }

        if ($sent === false) {
            $log->update(['status' => 'failed', 'error' => 'Not delivered: VoIP.ms timed out and has no record of sending this message. Please resend.']);
            $this->message()?->update(['unconfirmed' => false, 'failed' => true]);
            Log::warning('[VoIP.ms] Unconfirmed send was not delivered', ['sms_log_id' => $log->id]);
        }
        // $sent === null on the last try: VoIP.ms couldn't be reached — leave it unconfirmed.
    }

    private function message(): ?SmsMessage
    {
        return $this->smsMessageId ? SmsMessage::find($this->smsMessageId) : null;
    }
}
