<?php

namespace App\Services;

use App\Jobs\VerifyVoipMsSend;
use App\Models\Customer;
use App\Models\SmsConversation;
use App\Models\SmsMessage;
use Illuminate\Validation\ValidationException;

/**
 * Sending from the SMS inbox — shared by the desktop portal and the mobile pages so
 * both follow the same rules (reply on the conversation's own line, pictures only on
 * the main line, "unconfirmed" warning when VoIP.ms times out).
 */
class SmsPortalSender
{
    public function rules(): array
    {
        return [
            'body'     => 'nullable|string|max:1600|required_without:photos',
            'photos'   => 'nullable|array|max:' . VoipMsSmsService::MAX_MEDIA,
            'photos.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:16384',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'Type a message or attach a picture.',
            'photos.max'            => 'You can attach up to ' . VoipMsSmsService::MAX_MEDIA . ' pictures per message.',
            'photos.*.image'        => 'Pictures must be JPEG, PNG, GIF or WebP.',
            'photos.*.mimes'        => 'Pictures must be JPEG, PNG, GIF or WebP.',
            'photos.*.max'          => 'Each picture must be under 16 MB.',
        ];
    }

    /**
     * Reply on an existing conversation, through whichever channel it's on so it goes
     * out as the same number the customer texted in on.
     *
     * @return array{ok: bool, flash: array{0: string, 1: string}}
     */
    public function reply(SmsConversation $conversation, ?string $body, array $photos): array
    {
        $body       = $body ?? '';
        $smsService = $this->serviceFor($conversation->channel);
        $media      = $this->storeAttachments($photos, $conversation->channel);

        if (! $this->send($smsService, $conversation->phone, $body, 'portal_reply', $conversation->customer, $media)) {
            return ['ok' => false, 'flash' => ['error', 'Failed to send SMS: ' . ($smsService->lastError ?? 'check SMS settings.')]];
        }

        $this->record($conversation, $body, $media, $smsService);

        return ['ok' => true, 'flash' => $this->sentFlash($smsService)];
    }

    /**
     * Start (or continue) a conversation with a phone number.
     *
     * @return array{ok: bool, conversation: ?SmsConversation, flash: array{0: string, 1: string}}
     */
    public function compose(string $phone, ?Customer $customer, ?string $body, array $photos): array
    {
        $body       = $body ?? '';
        $normalized = app(SmsService::class)->normalizePhone($phone);

        // Keep an ongoing thread on whichever channel it's already using; a brand-new
        // conversation defaults to the shop's main line (VoIP.ms).
        $existing   = SmsConversation::where('phone', $normalized)->first();
        $channel    = $existing?->channel ?? 'voipms';
        $smsService = $this->serviceFor($channel);
        $media      = $this->storeAttachments($photos, $channel);

        if (! $this->send($smsService, $normalized, $body, 'portal_outbound', $customer, $media)) {
            return ['ok' => false, 'conversation' => null, 'flash' => ['error', 'Failed to send SMS: ' . ($smsService->lastError ?? 'check SMS settings.')]];
        }

        $conversation = SmsConversation::firstOrCreate(
            ['phone' => $normalized],
            ['status' => 'active', 'channel' => $channel, 'customer_id' => $customer?->id]
        );

        if ($customer && ! $conversation->customer_id) {
            $conversation->update(['customer_id' => $customer->id]);
        }

        $this->record($conversation, $body, $media, $smsService);

        return ['ok' => true, 'conversation' => $conversation, 'flash' => $this->sentFlash($smsService)];
    }

    private function serviceFor(?string $channel)
    {
        return $channel === 'voipms' ? app(VoipMsSmsService::class) : app(SmsService::class);
    }

    private function send($smsService, string $phone, string $body, string $type, ?Customer $customer, array $media): bool
    {
        $sent = $media
            ? $smsService->send($phone, $body, $type, $customer, $media)
            : $smsService->send($phone, $body, $type, $customer);

        if (! $sent) {
            app(SmsMediaStore::class)->delete($media);
        }

        return (bool) $sent;
    }

    private function record(SmsConversation $conversation, string $body, array $media, $smsService): void
    {
        $unconfirmed = $smsService->unconfirmed ?? false;

        $message = SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'body'            => $body,
            'media'           => $media ?: null,
            'sent_by_id'      => auth()->id(),
        ] + ($unconfirmed ? ['unconfirmed' => true] : []));

        // Timed out: check VoIP.ms's sent history in a minute and settle it either way.
        if ($unconfirmed && $smsService instanceof VoipMsSmsService && $smsService->lastLogId) {
            VerifyVoipMsSend::dispatch($smsService->lastLogId, $message->id)->delay(now()->addMinute());
        }

        $conversation->update(['last_message_at' => now()]);
    }

    /**
     * Saves attached pictures, shrunk for MMS. Only the VoIP.ms main line can send
     * pictures — Twilio reminders conversations stay text-only.
     */
    private function storeAttachments(array $files, ?string $channel): array
    {
        if (! $files) {
            return [];
        }

        if ($channel !== 'voipms') {
            throw ValidationException::withMessages([
                'photos' => 'Pictures can only be sent from the main line (604-299-4447). This conversation is on the reminders number.',
            ]);
        }

        $store = app(SmsMediaStore::class);

        return array_map(fn ($file) => $store->storeUpload($file), $files);
    }

    /**
     * A send that timed out may still have gone out — warn instead of claiming success,
     * so nobody resends and double-texts the customer.
     */
    private function sentFlash($smsService): array
    {
        if ($smsService->unconfirmed ?? false) {
            return ['warning', 'VoIP.ms was slow to respond, so this message is marked unconfirmed. It has most likely gone out — don\'t resend. The app will check with VoIP.ms over the next few minutes and update it to sent, or to "Not delivered" if it really didn\'t go.'];
        }

        return ['success', 'Message sent.'];
    }
}
