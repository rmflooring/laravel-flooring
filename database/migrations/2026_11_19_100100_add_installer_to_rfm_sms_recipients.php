<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RFMs can now have an installer. Add "installer" to the saved RFM SMS
 * recipient lists so installers get booked/updated texts and the
 * day-before reminder without an admin having to tick it first.
 */
return new class extends Migration
{
    private const KEYS = ['sms_rfm_booked_to', 'sms_rfm_updated_to', 'sms_rfm_reminder_to'];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            $row = DB::table('app_settings')->where('key', $key)->first();
            if (! $row) {
                continue; // code default already includes installer
            }
            $list = array_values(array_filter(explode(',', (string) $row->value)));
            if (! in_array('installer', $list, true)) {
                $list[] = 'installer';
                DB::table('app_settings')->where('key', $key)->update(['value' => implode(',', $list)]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::KEYS as $key) {
            $row = DB::table('app_settings')->where('key', $key)->first();
            if (! $row) {
                continue;
            }
            $list = array_values(array_filter(explode(',', (string) $row->value), fn ($v) => $v !== 'installer'));
            DB::table('app_settings')->where('key', $key)->update(['value' => implode(',', $list)]);
        }
    }
};
