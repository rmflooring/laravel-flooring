<?php

namespace App\Console\Commands;

use App\Models\SmsConversation;
use App\Models\SmsMessage;
use App\Services\InboundSmsRecorder;
use App\Services\SmsMediaStore;
use App\Services\SmsService;
use App\Services\VoipMsSmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Pulls in pictures customers texted to the main line before the SMS portal kept MMS
 * media: a picture-only text was dropped outright, and a picture with a caption kept
 * just the caption. Safe to re-run — anything already imported is skipped by its
 * VoIP.ms id.
 */
class ImportVoipMsPictures extends Command
{
    protected $signature = 'sms:import-voipms-pictures
                            {--days=90 : How far back to look}
                            {--dry-run : List what would be imported without saving anything}';

    protected $description = 'Import pictures texted to the VoIP.ms main line that the SMS portal missed';

    public function handle(VoipMsSmsService $voipMs, InboundSmsRecorder $recorder, SmsMediaStore $store): int
    {
        $days = max(1, (int) $this->option('days'));
        $rows = collect($voipMs->receivedMms(now()->subDays($days)->toDateString(), now()->toDateString()))
            ->filter(fn (array $row) => ! empty($row['media']))
            ->sortBy('date')
            ->values();

        $this->info("Found {$rows->count()} received picture message(s) in the last {$days} days.");

        $imported = $attached = $skipped = 0;

        foreach ($rows as $row) {
            $id    = (string) $row['id'];
            $body  = trim((string) ($row['message'] ?? ''));
            $phone = (string) $row['contact'];
            // VoIP.ms reports dates in the account's timezone, set to Pacific like the app
            $at    = Carbon::parse($row['date'], config('app.timezone'));
            $label = "{$at->format('M j, Y g:ia')} from {$phone}" . ($body !== '' ? ' — "' . mb_strimwidth(\Illuminate\Support\Str::squish($body), 0, 40, '…') . '"' : '');

            if (SmsMessage::where('voipms_id', $id)->exists()) {
                $skipped++;
                continue;
            }

            // A captioned picture already has its text in the portal — add the picture to it
            $existing = $body === '' ? null : $this->findCaptionMessage($phone, $body, $at);

            if ($this->option('dry-run')) {
                $this->line(($existing ? '  attach to existing message: ' : '  import: ') . $label);
                continue;
            }

            if ($existing) {
                $media = array_values(array_filter(array_map(fn ($url) => $store->storeFromUrl($url), $row['media'])));
                $existing->update(['media' => $media ?: null, 'voipms_id' => $id]);
                $attached++;
                $this->line("  attached to existing message: {$label}");
            } else {
                $recorder->record('voipms', $phone, $body, $row['media'], $id, $at, alert: false);
                $imported++;
                $this->line("  imported: {$label}");
            }
        }

        $this->info("Done — {$imported} imported, {$attached} attached to existing messages, {$skipped} already imported.");

        return self::SUCCESS;
    }

    private function findCaptionMessage(string $phone, string $body, Carbon $at): ?SmsMessage
    {
        $conversation = SmsConversation::where('phone', app(SmsService::class)->normalizePhone($phone))->first();

        if (! $conversation) {
            return null;
        }

        return SmsMessage::where('conversation_id', $conversation->id)
            ->where('direction', 'inbound')
            ->whereNull('media')
            ->where('body', $body)
            ->whereBetween('created_at', [$at->copy()->subDay(), $at->copy()->addDay()])
            ->first();
    }
}
