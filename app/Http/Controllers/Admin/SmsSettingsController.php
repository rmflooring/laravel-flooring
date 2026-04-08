<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.sms', [
            'smsEnabled'          => Setting::get('sms_enabled', '0'),
            'smsAccountSid'       => Setting::get('sms_account_sid', ''),
            'smsAuthToken'        => Setting::get('sms_auth_token', ''),
            'smsFromNumber'       => Setting::get('sms_from_number', ''),
            'smsReminderTime'     => Setting::get('sms_reminder_time', '16:00'),
            // Per-notification toggles
            'notifyWoScheduled'   => Setting::get('sms_notify_wo_scheduled', '0'),
            'notifyWoReminder'    => Setting::get('sms_notify_wo_reminder', '0'),
            'notifyRfmBooked'     => Setting::get('sms_notify_rfm_booked', '0'),
            'notifyRfmUpdated'    => Setting::get('sms_notify_rfm_updated', '0'),
            'notifyRfmReminder'       => Setting::get('sms_notify_rfm_reminder', '0'),
            'sampleSmsReminders'      => Setting::get('sample_sms_reminders_enabled', '1'),
            'sampleReminderDays'      => Setting::get('sample_reminder_days', '3'),
            // Recipients per notification
            'woScheduledTo'       => Setting::get('sms_wo_scheduled_to', 'pm,installer'),
            'woReminderTo'        => Setting::get('sms_wo_reminder_to', 'pm,installer'),
            'rfmBookedTo'         => Setting::get('sms_rfm_booked_to', 'estimator,pm'),
            'rfmUpdatedTo'        => Setting::get('sms_rfm_updated_to', 'estimator,pm'),
            'rfmReminderTo'       => Setting::get('sms_rfm_reminder_to', 'estimator,pm'),
            // Send log
            'smsLogs'             => SmsLog::latest()->take(100)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'sms_account_sid'   => 'nullable|string|max:100',
            'sms_auth_token'    => 'nullable|string|max:100',
            'sms_from_number'   => 'nullable|string|max:30',
            'sms_reminder_time' => 'nullable|string|max:5',
        ]);

        Setting::set('sms_enabled',        $request->input('sms_enabled') === '1' ? '1' : '0');
        Setting::set('sms_account_sid',    $request->input('sms_account_sid', ''));
        Setting::set('sms_auth_token',     $request->input('sms_auth_token', ''));
        Setting::set('sms_from_number',    $request->input('sms_from_number', ''));
        Setting::set('sms_reminder_time',  $request->input('sms_reminder_time', '16:00'));

        // Per-notification toggles
        Setting::set('sms_notify_wo_scheduled', $request->input('sms_notify_wo_scheduled') === '1' ? '1' : '0');
        Setting::set('sms_notify_wo_reminder',  $request->input('sms_notify_wo_reminder') === '1' ? '1' : '0');
        Setting::set('sms_notify_rfm_booked',   $request->input('sms_notify_rfm_booked') === '1' ? '1' : '0');
        Setting::set('sms_notify_rfm_updated',  $request->input('sms_notify_rfm_updated') === '1' ? '1' : '0');
        Setting::set('sms_notify_rfm_reminder', $request->input('sms_notify_rfm_reminder') === '1' ? '1' : '0');
        Setting::set('sample_sms_reminders_enabled', $request->input('sample_sms_reminders_enabled') === '1' ? '1' : '0');
        Setting::set('sample_reminder_days', max(1, (int) $request->input('sample_reminder_days', 3)));

        // Recipients
        Setting::set('sms_wo_scheduled_to', implode(',', (array) $request->input('sms_wo_scheduled_to', [])));
        Setting::set('sms_wo_reminder_to',  implode(',', (array) $request->input('sms_wo_reminder_to', [])));
        Setting::set('sms_rfm_booked_to',   implode(',', (array) $request->input('sms_rfm_booked_to', [])));
        Setting::set('sms_rfm_updated_to',  implode(',', (array) $request->input('sms_rfm_updated_to', [])));
        Setting::set('sms_rfm_reminder_to', implode(',', (array) $request->input('sms_rfm_reminder_to', [])));

        return back()->with('success', 'SMS settings saved.');
    }

    public function testSend(Request $request)
    {
        $request->validate([
            'test_number' => 'required|string|max:30',
        ]);

        $sms  = new SmsService();
        $sent = $sms->send(
            $request->input('test_number'),
            'This is a test SMS from Floor Manager (RM Flooring).',
            'test'
        );

        if ($sent) {
            return back()->with('success', 'Test SMS sent successfully.');
        }

        return back()->with('error', 'Test SMS failed — check your credentials and try again. See the send log for details.');
    }
}
