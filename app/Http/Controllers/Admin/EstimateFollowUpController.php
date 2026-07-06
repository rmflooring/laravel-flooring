<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\EstimateFollowUp;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EstimateFollowUpController extends Controller
{
    public function sendEmail(Request $request, Estimate $estimate)
    {
        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
            'stage'   => ['required', 'integer', 'between:1,3'],
        ]);

        $user   = auth()->user();
        $mailer = app(GraphMailService::class);

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $request->to, $request->subject, $request->body, 'estimate_follow_up')
            : false;

        if (! $sent) {
            $sent = $mailer->send($request->to, $request->subject, $request->body, 'estimate_follow_up');
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send follow-up email.');
        }

        EstimateFollowUp::create([
            'estimate_id' => $estimate->id,
            'user_id'     => $user->id,
            'stage'       => $request->stage,
            'channel'     => 'email',
            'sent_to'     => $request->to,
            'sent_at'     => now(),
        ]);

        return back()->with('success', 'Follow-up email sent to ' . $request->to . '.');
    }

    public function sendSms(Request $request, Estimate $estimate)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'body'  => ['required', 'string', 'max:320'],
            'stage' => ['required', 'integer', 'between:1,3'],
        ]);

        $sms  = new SmsService();
        $sent = $sms->send($request->phone, $request->body, 'estimate_follow_up', $estimate);

        if (! $sent) {
            return back()->with('error', 'Failed to send follow-up SMS.');
        }

        EstimateFollowUp::create([
            'estimate_id' => $estimate->id,
            'user_id'     => auth()->id(),
            'stage'       => $request->stage,
            'channel'     => 'sms',
            'sent_to'     => $request->phone,
            'sent_at'     => now(),
        ]);

        return back()->with('success', 'Follow-up SMS sent to ' . $request->phone . '.');
    }

    public function logNote(Request $request, Estimate $estimate)
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
            'stage' => ['required', 'integer', 'between:1,3'],
        ]);

        EstimateFollowUp::create([
            'estimate_id' => $estimate->id,
            'user_id'     => auth()->id(),
            'stage'       => $request->stage,
            'channel'     => 'note',
            'notes'       => $request->notes,
        ]);

        return back()->with('success', 'Follow-up note saved.');
    }

    public function markClosed(Request $request, Estimate $estimate)
    {
        $estimate->update(['follow_up_closed' => true]);

        return back()->with('success', 'Estimate removed from the follow-up queue.');
    }

    public function reopen(Request $request, Estimate $estimate)
    {
        $estimate->update(['follow_up_closed' => false]);

        return back()->with('success', 'Estimate returned to the follow-up queue.');
    }

    /**
     * Return a pre-filled draft (subject + body) for the given stage as JSON.
     * Used by the modal to populate the email/SMS fields when it opens.
     */
    public function draft(Request $request, Estimate $estimate)
    {
        $request->validate(['stage' => ['required', 'integer', 'between:1,3']]);

        $stage        = (int) $request->stage;
        $user         = auth()->user();
        $tplService   = new EmailTemplateService();
        $smsTpl       = new SmsTemplateService();
        $customerName = $estimate->homeowner_name ?: $estimate->customer_name ?: '';
        $daysSinceSent = $estimate->first_sent_at
            ? (int) $estimate->first_sent_at->diffInDays(now())
            : 0;

        $vars = [
            'customer_name'    => $customerName,
            'estimate_number'  => $estimate->estimate_number ?? '',
            'grand_total'      => $estimate->grand_total ? '$' . number_format($estimate->grand_total, 2) : '',
            'job_name'         => $estimate->job_name ?? '',
            'job_no'           => $estimate->job_no ?? '',
            'job_address'      => $estimate->job_address ?? '',
            'job_phone'        => $estimate->homeowner_phone ?? '',
            'job_mobile'       => $estimate->homeowner_mobile ?? '',
            'days_since_sent'  => $daysSinceSent,
            'sender_name'      => $user->name ?? '',
            'sender_email'     => $user->email ?? '',
        ];

        $emailType     = "estimate_follow_up_{$stage}";
        $emailTemplate = $tplService->getTemplate($user, $emailType);

        return response()->json([
            'email' => [
                'subject' => $tplService->render($emailTemplate['subject'], $vars),
                'body'    => $tplService->render($emailTemplate['body'], $vars),
                'to'      => $estimate->homeowner_email ?? '',
            ],
            'sms' => [
                'body'  => $smsTpl->renderTemplate($emailType, $vars),
                'phone' => $estimate->homeowner_phone ?? $estimate->homeowner_mobile ?? '',
            ],
        ]);
    }
}
