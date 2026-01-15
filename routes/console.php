<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\MicrosoftCalendarConnectController;
use Illuminate\Http\Request;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
