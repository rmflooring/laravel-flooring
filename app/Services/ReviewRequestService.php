<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ReviewRequestService
{
    /**
     * Build the default SMS body for a review request (used for both the
     * initial send and the 7-day reminder).
     */
    public function smsMessage(string $name, string $url): string
    {
        return app(SmsTemplateService::class)->renderTemplate('review_request', [
            'customer_name' => $name,
            'review_link'   => $url,
        ]);
    }

    public function sendEmail(string $email, string $name, string $url, ?string $customMessage = null): void
    {
        try {
            $service  = app(EmailTemplateService::class);
            $template = $service->getTemplate(null, 'review_request');

            $vars = [
                'customer_name' => $name,
                'review_link'   => $url,
            ];

            // A staff-typed custom message (from the send form) replaces the
            // template body outright — it's free text, not itself a template.
            $usingCustom = ! empty($customMessage);
            $subject     = $service->render($template['subject'], $vars);
            $body        = $service->render($usingCustom ? $customMessage : $template['body'], $vars);

            $escaped = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));

            $buttonHtml =
                '<div style="margin:20px 0;">' .
                '<a href="' . $url . '" ' .
                   'style="display:inline-block;background-color:#1a56db;color:#ffffff;font-family:sans-serif;' .
                          'font-size:15px;font-weight:600;text-decoration:none;padding:12px 24px;border-radius:6px;">' .
                    'Leave a Review' .
                '</a>' .
                '</div>';

            // The stored template places the button via {{review_link_button}};
            // a raw custom message won't contain that token, so append it instead.
            $content = str_contains($escaped, '{{review_link_button}}')
                ? str_replace('{{review_link_button}}', $buttonHtml, $escaped)
                : $escaped . $buttonHtml;

            $htmlBody = '<div style="font-family:sans-serif;font-size:14px;line-height:1.6;color:#222;">'
                . $content
                . '<p style="margin-top:24px;font-size:12px;color:#6b7280;">RM Flooring · Coquitlam, BC</p>'
                . '</div>';

            app(GraphMailService::class)->send(
                to: $email,
                subject: $subject,
                body: $htmlBody,
                isHtml: true,
            );
        } catch (\Throwable $e) {
            Log::error('[ReviewRequest] Email failed', ['error' => $e->getMessage()]);
        }
    }
}
