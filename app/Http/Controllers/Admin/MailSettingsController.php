<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\GraphMailService;
use Illuminate\Http\Request;

class MailSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.mail', [
            'mailFromAddress'         => Setting::get('mail_from_address', config('services.microsoft.mail_from_address', 'reception@rmflooring.ca')),
            'mailFromName'            => Setting::get('mail_from_name', 'RM Flooring Notifications'),
            'mailReplyTo'             => Setting::get('mail_reply_to', 'noreply@rmflooring.ca'),
            'mailNotificationsEnabled' => Setting::get('mail_notifications_enabled', '1'),
            'mailLogs'                => MailLog::latest()->take(50)->get(),
            'users'                   => User::with('microsoftAccount')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name'    => ['required', 'string', 'max:255'],
            'mail_reply_to'     => ['required', 'email', 'max:255'],
        ]);

        Setting::set('mail_from_address', $request->input('mail_from_address'));
        Setting::set('mail_from_name', $request->input('mail_from_name'));
        Setting::set('mail_reply_to', $request->input('mail_reply_to'));
        Setting::set('mail_notifications_enabled', $request->has('mail_notifications_enabled') ? '1' : '0');

        return back()->with('success', 'Mail settings saved.');
    }

    public function testUserSend(Request $request, User $user)
    {
        $account = $user->microsoftAccount;

        if (! $account || ! $account->mail_connected) {
            return back()->with('error', "{$user->name} does not have Track 2 mail connected.");
        }

        $mailer  = app(GraphMailService::class);
        $success = $mailer->sendAsUser(
            user:    $user,
            to:      $user->email,
            subject: 'Floor Manager — Track 2 Test Email',
            body:    "This is a test email sent via Track 2 (per-user delegated OAuth).\n\nSent from: {$user->name} ({$account->email})\n\nIf you received this, Track 2 email is working correctly for this account.",
            type:    'test',
        );

        if ($success) {
            return back()->with('success', "Track 2 test email sent successfully from {$account->email} to {$user->email}.");
        }

        return back()->with('error', "Track 2 test failed for {$user->name}. The token may have expired — try reconnecting.");
    }

    public function testSend(Request $request)
    {
        $request->validate([
            'test_to' => ['required', 'email'],
        ]);

        $mailer  = app(GraphMailService::class);
        $success = $mailer->send(
            to:      $request->input('test_to'),
            subject: 'Floor Manager — Test Email',
            body:    "This is a test email sent from the Floor Manager Email Management portal.\n\nIf you received this, Track 1 email is working correctly.",
            type:    'test',
        );

        if ($success) {
            return back()->with('success', 'Test email sent successfully to ' . $request->input('test_to') . '.');
        }

        return back()->with('error', 'Test email failed. Check the application logs for details.');
    }
}
