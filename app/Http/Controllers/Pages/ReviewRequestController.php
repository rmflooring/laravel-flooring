<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\ReviewRequest;
use App\Services\SmsService;
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
            ?? "Hi {$name}, thank you for choosing RM Flooring! We'd love to hear about your experience. Please take a moment to leave us a review: {$url}";

        if ($validated['sent_via'] === 'sms' && ! empty($validated['customer_phone'])) {
            app(SmsService::class)->send($validated['customer_phone'], $message, 'review_request', $opportunity);
        } elseif ($validated['sent_via'] === 'email' && ! empty($validated['customer_email'])) {
            $this->sendEmail($validated['customer_email'], $name, $url, $message);
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

    private function sendEmail(string $email, string $name, string $url, string $message): void
    {
        try {
            $html = "
                <p>Hi {$name},</p>
                <p>" . nl2br(e($message)) . "</p>
                <p style='margin-top:24px;'>
                    <a href='{$url}' style='background:#1a56db;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;'>
                        Leave a Review
                    </a>
                </p>
                <p style='margin-top:24px;font-size:12px;color:#6b7280;'>RM Flooring · Coquitlam, BC</p>
            ";

            app(\App\Services\GraphMailService::class)->send(
                to: $email,
                subject: 'How was your experience with RM Flooring?',
                body: $html,
            );
        } catch (\Throwable $e) {
            Log::error('[ReviewRequest] Email failed', ['error' => $e->getMessage()]);
        }
    }
}
