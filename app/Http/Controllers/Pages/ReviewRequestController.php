<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\ReviewRequest;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewRequestController extends Controller
{
    public function store(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|string|email|max:255',
            'sent_via'       => 'required|in:sms,email',
            'message'        => 'nullable|string|max:500',
        ]);

        $review = ReviewRequest::create([
            'opportunity_id' => $opportunity->id,
            'sent_by'        => auth()->id(),
            'customer_name'  => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,
            'sent_via'       => $validated['sent_via'],
        ]);

        $url     = $review->publicUrl();
        $name    = $validated['customer_name'];
        $message = $validated['message']
            ?? app(SmsTemplateService::class)->renderTemplate('review_request', [
                'customer_name' => $name,
                'review_link'   => $url,
            ]);

        if ($validated['sent_via'] === 'sms' && ! empty($validated['customer_phone'])) {
            app(SmsService::class)->send($validated['customer_phone'], $message, 'review_request', $opportunity);
        } elseif ($validated['sent_via'] === 'email' && ! empty($validated['customer_email'])) {
            $this->sendEmail($validated['customer_email'], $name, $url, $validated['message'] ?? null);
        }

        Log::info('[ReviewRequest] Sent', [
            'opportunity_id' => $opportunity->id,
            'via'            => $validated['sent_via'],
            'customer'       => $name,
        ]);

        return back()->with('success', "Review request sent to {$name}.");
    }

    public function index(Opportunity $opportunity)
    {
        $reviews = ReviewRequest::where('opportunity_id', $opportunity->id)
            ->with('sentBy')
            ->latest()
            ->get();

        return view('pages.opportunities.reviews.index', compact('opportunity', 'reviews'));
    }

    private function sendEmail(string $email, string $name, string $url, ?string $customMessage): void
    {
        try {
            $service  = app(\App\Services\EmailTemplateService::class);
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

            app(\App\Services\GraphMailService::class)->send(
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
