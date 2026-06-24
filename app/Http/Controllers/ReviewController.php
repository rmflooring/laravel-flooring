<?php

namespace App\Http\Controllers;

use App\Models\ReviewRequest;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    const GOOGLE_REVIEW_URL = 'https://g.page/r/CV3RLnJvEpzEEBM/review';
    const POSITIVE_THRESHOLD = 4;

    public function show(string $token)
    {
        $review = ReviewRequest::where('token', $token)
            ->with('opportunity')
            ->firstOrFail();

        if ($review->isSubmitted()) {
            return view('review.already-submitted', compact('review'));
        }

        return view('review.show', compact('review'));
    }

    public function submit(Request $request, string $token)
    {
        $review = ReviewRequest::where('token', $token)->firstOrFail();

        if ($review->isSubmitted()) {
            return view('review.already-submitted', compact('review'));
        }

        $validated = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $review->update([
            'rating'       => $validated['rating'],
            'feedback'     => $validated['feedback'] ?? null,
            'submitted_at' => now(),
        ]);

        if ($review->rating >= self::POSITIVE_THRESHOLD) {
            // High rating — redirect to Google
            return redirect(self::GOOGLE_REVIEW_URL);
        }

        // Low rating — notify staff and show thank-you
        $this->notifyStaff($review);

        return view('review.thank-you', compact('review'));
    }

    private function notifyStaff(ReviewRequest $review): void
    {
        $adminEmail = config('app.admin_notification_email', env('ADMIN_NOTIFICATION_EMAIL'));
        if (! $adminEmail) return;

        try {
            $mailer = app(\App\Services\GraphMailService::class);
            $stars  = str_repeat('★', $review->rating) . str_repeat('☆', 5 - $review->rating);
            $html   = "
                <p><strong>A customer left a low rating that needs your attention.</strong></p>
                <p><strong>Customer:</strong> {$review->customer_name}</p>
                <p><strong>Job:</strong> {$review->opportunity->job_name}</p>
                <p><strong>Rating:</strong> {$stars} ({$review->rating}/5)</p>
                <p><strong>Feedback:</strong><br>" . nl2br(e($review->feedback ?? '(none)')) . "</p>
                <p><a href='" . route('pages.opportunities.show', $review->opportunity_id) . "'>View Job in Floor Manager</a></p>
            ";

            $mailer->send(
                to: $adminEmail,
                subject: "⚠️ Low Review Rating — {$review->customer_name} ({$review->rating}/5 stars)",
                body: $html,
            );
        } catch (\Throwable) {
            // Non-critical — don't fail the request if email fails
        }
    }
}
