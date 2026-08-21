<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_conversations', function (Blueprint $table) {
            $table->string('channel')->default('twilio')->after('phone');
        });

        // Everything to date came in through the Twilio webhook — the column default
        // already covers this, but set it explicitly for existing rows for clarity.
        DB::table('sms_conversations')->update(['channel' => 'twilio']);
    }

    public function down(): void
    {
        Schema::table('sms_conversations', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
