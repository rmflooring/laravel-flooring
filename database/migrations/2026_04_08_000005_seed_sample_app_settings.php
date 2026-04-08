<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(['key' => 'sample_email_reminders_enabled'], ['value' => '1']);
        Setting::firstOrCreate(['key' => 'sample_sms_reminders_enabled'],   ['value' => '1']);
        Setting::firstOrCreate(['key' => 'sample_reminder_days'],           ['value' => '3']); // re-remind every N days after due
        Setting::firstOrCreate(['key' => 'sample_checkout_days'],           ['value' => '5']); // default due-back days
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'sample_email_reminders_enabled',
            'sample_sms_reminders_enabled',
            'sample_reminder_days',
            'sample_checkout_days',
        ])->delete();
    }
};
