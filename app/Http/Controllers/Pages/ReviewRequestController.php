<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\ReviewRequest;
use App\Services\ReviewRequestService;
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

        $reviewService = app(ReviewRequestService::class);

        $url     = $review->publicUrl();
        $name    = $validated['customer_name'];
        $message = $validated['message'] ?? $reviewService->smsMessage($name, $url);

        if ($validated['sent_via'] === 'sms' && ! empty($validated['customer_phone'])) {
            app(SmsService::class)->send($validated['customer_phone'], $message, 'review_request', $opportunity);
        } elseif ($validated['sent_via'] === 'email' && ! empty($validated['customer_email'])) {
            $reviewService->sendEmail($validated['customer_email'], $name, $url, $validated['message'] ?? null);
        }

        Log::info('[ReviewRequest] Sent', [
            'opportunity_id' => $opportunity->id,
            'via'            => $validated['sent_via'],
            'customer'       => $name,
        ]);

        return back()->with('success', "Review request sent to {$name}.");
    }

    /**
     * Site-wide list of every review request sent, for staff to track what's
     * outstanding and follow up on low ratings.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'needs_followup');
        if (! in_array($status, ['needs_followup', 'positive', 'pending', 'all'], true)) {
            $status = 'needs_followup';
        }

        $query = ReviewRequest::with(['opportunity.jobSiteCustomer', 'sentBy'])->latest();

        match ($status) {
            'needs_followup' => $query->whereNotNull('submitted_at')->where('rating', '<=', 3),
            'positive'        => $query->whereNotNull('submitted_at')->where('rating', '>=', 4),
            'pending'         => $query->whereNull('submitted_at'),
            default           => null, // 'all' — no extra filter
        };

        $reviewRequests = $query->paginate(25)->withQueryString();

        $needsFollowupCount = ReviewRequest::whereNotNull('submitted_at')->where('rating', '<=', 3)->count();

        return view('pages.review-requests.index', compact('reviewRequests', 'status', 'needsFollowupCount'));
    }
}
