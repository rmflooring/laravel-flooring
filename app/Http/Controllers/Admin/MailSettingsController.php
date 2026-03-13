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
