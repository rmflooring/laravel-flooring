<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\MicrosoftCalendarConnectController;
use Illuminate\Http\Request;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NAS health check — runs every 5 minutes, alerts richard@rmflooring.ca on status change
Schedule::command('nas:check-health')
    ->everyFiveMinutes()
    ->name('nas-check-health')
    ->withoutOverlapping();

// SMS day-before reminders — runs daily at the configured time (default 4pm)
Schedule::command('sms:send-reminders')
    ->dailyAt(\App\Models\Setting::get('sms_reminder_time', '16:00'))
    ->timezone('America/Vancouver')
    ->name('sms-send-reminders')
    ->withoutOverlapping();

// Sample overdue reminders — runs daily at 9am, emails + SMS customers with overdue samples
Schedule::command('samples:send-reminders')
    ->dailyAt('09:00')
    ->timezone('America/Vancouver')
    ->name('samples-send-reminders')
    ->withoutOverlapping();

// AP: flip pending bills past their due date to overdue — runs daily at 8am
Schedule::command('bills:mark-overdue')
    ->dailyAt('08:00')
    ->timezone('America/Vancouver')
    ->name('bills-mark-overdue')
    ->withoutOverlapping();

// Check if estimators have accepted their RFM calendar invites → mark confirmed
Schedule::command('rfm:check-confirmations')
    ->everyTenMinutes()
    ->name('rfm-check-confirmations')
    ->withoutOverlapping();

Schedule::call(function () {
    $users = \App\Models\User::whereHas('microsoftAccount', function ($q) {
        $q->where('is_connected', 1);
    })->get();

    foreach ($users as $user) {
        // Fake a request for THIS user, so syncNow() uses their Microsoft account + enabled calendars
	    \Log::info('Auto-sync starting', ['user_id' => $user->id]);
		
        $request = Request::create('/settings/integrations/microsoft/sync-now', 'POST');
        $request->setUserResolver(fn () => $user);

        app(MicrosoftCalendarConnectController::class)->syncNow($request);
    }
})
->everyTenMinutes()
->name('microsoft-calendar-auto-sync')
->withoutOverlapping();
