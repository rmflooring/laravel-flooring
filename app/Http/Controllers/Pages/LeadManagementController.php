<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IncomingLead;
use App\Models\MailLog;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\SmsConversation;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\GraphMailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadManagementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        if (! in_array($status, ['pending', 'approved', 'denied'])) {
            $status = 'pending';
        }

        $leads = IncomingLead::where('status', $status)
            ->with('reviewer')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pendingCount = IncomingLead::where('status', 'pending')->count();

        return view('pages.leads.index', compact('leads', 'status', 'pendingCount'));
    }

    public function show(IncomingLead $lead)
    {
        $lead->load('opportunity.parentCustomer', 'reviewer');
        $users = User::orderBy('name')->get(['id', 'name']);

        $emailReplies = MailLog::where('related_type', IncomingLead::class)
            ->where('related_id', $lead->id)
            ->orderByDesc('created_at')
            ->get();

        $smsService      = app(SmsService::class);
        $smsConversation = null;
        if ($lead->phone) {
            $normalized      = $smsService->normalizePhone($lead->phone);
            $smsConversation = SmsConversation::where('phone', $normalized)
                ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
                ->first();
        }

        return view('pages.leads.show', compact('lead', 'users', 'emailReplies', 'smsConversation'));
    }

    public function approve(Request $request, IncomingLead $lead)
    {
        if (! $lead->isPending()) {
            return back()->with('error', 'This lead has already been reviewed.');
        }

        $validated = $request->validate([
            'opportunity_name' => 'required|string|max:255',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string|max:5000',
        ]);

        $opportunity = DB::transaction(function () use ($lead, $validated) {
            $customer = Customer::create([
                'name'  => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'notes' => 'Created from web lead — ' . $lead->source,
            ]);

            $opportunity = Opportunity::create([
                'parent_customer_id'   => $customer->id,
                'job_site_customer_id' => $customer->id,
                'job_no'               => $validated['opportunity_name'],
                'status'               => 'New',
            ]);

            $noteLines = array_filter([
                $lead->service_type    ? 'Service: ' . $lead->service_type           : null,
                $lead->project_type    ? 'Project Type: ' . $lead->project_type       : null,
                $lead->area            ? 'Area: ' . $lead->area                       : null,
                $lead->city            ? 'City: ' . $lead->city                       : null,
                $lead->timeline        ? 'Timeline: ' . $lead->timeline               : null,
                $lead->referral_source ? 'Referral: ' . $lead->referral_source        : null,
                $lead->message         ? "\n" . $lead->message                        : null,
                ! empty($validated['notes']) && $validated['notes'] !== $lead->message
                    ? "\n---\n" . $validated['notes']
                    : null,
            ]);

            if (! empty($noteLines)) {
                OpportunityNote::create([
                    'opportunity_id' => $opportunity->id,
                    'user_id'        => auth()->id(),
                    'body'           => implode("\n", $noteLines),
                ]);
            }

            $lead->update([
                'status'         => 'approved',
                'opportunity_id' => $opportunity->id,
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now(),
            ]);

            return $opportunity;
        });

        return redirect()
            ->route('pages.opportunities.show', $opportunity)
            ->with('success', 'Lead approved — Opportunity created.');
    }

    public function deny(Request $request, IncomingLead $lead)
    {
        if (! $lead->isPending()) {
            return back()->with('error', 'This lead has already been reviewed.');
        }

        $validated = $request->validate([
            'denial_reason' => 'nullable|string|max:1000',
        ]);

        $lead->update([
            'status'        => 'denied',
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'denial_reason' => $validated['denial_reason'] ?? null,
        ]);

        return redirect()
            ->route('pages.leads.index')
            ->with('success', 'Lead denied.');
    }

    public function replyEmail(Request $request, IncomingLead $lead)
    {
        if (! $lead->email) {
            return back()->with('error', 'This lead has no email address.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string|max:10000',
        ]);

        $sent = app(GraphMailService::class)->send(
            to:          $lead->email,
            subject:     $validated['subject'],
            body:        nl2br(htmlspecialchars($validated['body'], ENT_QUOTES, 'UTF-8')),
            type:        'lead_reply',
            relatedId:   $lead->id,
            relatedType: IncomingLead::class,
        );

        if (! $sent) {
            return back()->withInput()->with('error', 'Email failed to send. Check mail settings.');
        }

        return back()->with('success', 'Email sent to ' . $lead->email . '.');
    }

    public function replySms(Request $request, IncomingLead $lead)
    {
        if (! $lead->phone) {
            return back()->with('error', 'This lead has no phone number.');
        }

        if (! $lead->sms_consent) {
            return back()->with('error', 'This lead has not consented to SMS.');
        }

        $validated = $request->validate([
            'body' => 'required|string|max:1600',
        ]);

        $smsService   = app(SmsService::class);
        $normalized   = $smsService->normalizePhone($lead->phone);

        $sent = $smsService->send($normalized, $validated['body'], 'lead_reply');

        if (! $sent) {
            return back()->withInput()->with('error', 'SMS failed to send. Check SMS settings.');
        }

        // Find or create a portal conversation for this number
        $conversation = SmsConversation::firstOrCreate(
            ['phone' => $normalized],
            ['status' => 'active']
        );

        // If the lead is approved and has a linked opportunity, wire it up
        if ($lead->opportunity_id && ! $conversation->opportunity_id) {
            $conversation->update([
                'customer_id'    => $lead->opportunity?->parentCustomer?->id,
                'opportunity_id' => $lead->opportunity_id,
            ]);
        }

        SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'body'            => $validated['body'],
            'sent_by_id'      => auth()->id(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'SMS sent to ' . $lead->phone . '.');
    }
}
