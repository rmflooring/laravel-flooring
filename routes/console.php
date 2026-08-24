<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\MicrosoftCalendarConnectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NAS health check — runs every 5 minutes, alerts richard@rmflooring.ca on status change
Schedule::command('nas:check-health')
    ->everyFiveMinutes()
    ->name('nas-check-health')
    ->withoutOverlapping();

// SMS day-before reminders — runs daily at the configured time (default 4pm)
// Guarded: routes/console.php loads at framework boot, before RefreshDatabase
// migrates the PHPUnit testing DB — querying app_settings before it exists
// would break every feature test's bootstrap.
Schedule::command('sms:send-reminders')
    ->dailyAt(Schema::hasTable('app_settings') ? \App\Models\Setting::get('sms_reminder_time', '16:00') : '16:00')
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

// Product styles: apply scheduled price changes at midnight
Schedule::command('styles:apply-scheduled-prices')
    ->dailyAt('00:00')
    ->timezone('America/Vancouver')
    ->name('styles-apply-scheduled-prices')
    ->withoutOverlapping();

// Check if estimators have accepted their RFM calendar invites → mark confirmed
Schedule::command('rfm:check-confirmations')
    ->everyTenMinutes()
    ->name('rfm-check-confirmations')
    ->withoutOverlapping();

// Poll the agent inbox for new mail-triggered AI Agent tasks
Schedule::command('agent:check-inbound-mail')
    ->everyTwoMinutes()
    ->name('agent-check-inbound-mail')
    ->withoutOverlapping();

Schedule::call(function () {
    $users = \App\Models\User::whereHas('microsoftAccount', function ($q) {
        $q->where('is_connected', 1);
    })->get();

    foreach ($users as $user) {
        \Log::info('Auto-sync starting', ['user_id' => $user->id]);

        try {
            $account = $user->microsoftAccount;

            // Check enabled calendars before invoking syncNow so we can log the skip reason
            $enabledCount = \App\Models\MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('is_enabled', true)
                ->count();

            if ($enabledCount === 0) {
                \Log::info('Auto-sync skipped — no enabled calendars', ['user_id' => $user->id]);
                continue;
            }

            $request = Request::create('/settings/integrations/microsoft/sync-now', 'POST');
            $request->setUserResolver(fn () => $user);

            app(MicrosoftCalendarConnectController::class)->syncNow($request);

            \Log::info('Auto-sync completed', ['user_id' => $user->id]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::warning('Auto-sync: could not decrypt tokens for user, disconnecting account', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            $user->microsoftAccount?->update([
                'is_connected'    => false,
                'access_token'    => null,
                'refresh_token'   => null,
                'disconnected_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Auto-sync: unexpected error for user', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
})
->everyTenMinutes()
->name('microsoft-calendar-auto-sync')
->withoutOverlapping();

// E-Signature: expire overdue requests daily at 8am
Schedule::command('signing:expire-requests')
    ->dailyAt('08:00')
    ->timezone('America/Vancouver')
    ->name('signing-expire-requests')
    ->withoutOverlapping();

// E-Signature: send reminders at 3, 7, 9 days daily at 9am
Schedule::command('signing:send-reminders')
    ->dailyAt('09:00')
    ->timezone('America/Vancouver')
    ->name('signing-send-reminders')
    ->withoutOverlapping();

// Estimate follow-ups: flag sent estimates due for stage 1/2/3 and notify estimator
Schedule::command('estimates:check-follow-ups')
    ->dailyAt('09:00')
    ->timezone('America/Vancouver')
    ->name('estimates-check-follow-ups')
    ->withoutOverlapping();

// Opportunities: advance Awaiting Site Measure → In Progress once the RFM visit date has passed
Schedule::command('opportunities:advance-overdue-rfm')
    ->dailyAt('06:00')
    ->timezone('America/Vancouver')
    ->name('opportunities-advance-overdue-rfm')
    ->withoutOverlapping();

// Opportunities: safety net — clear "Requires RFM" on any opportunity that already has one booked
// (normally cleared the moment the RFM is created; this catches edge cases/legacy data)
Schedule::command('opportunities:sync-requires-rfm')
    ->dailyAt('06:05')
    ->timezone('America/Vancouver')
    ->name('opportunities-sync-requires-rfm')
    ->withoutOverlapping();
