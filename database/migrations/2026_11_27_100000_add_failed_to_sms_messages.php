<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // An unconfirmed send that VerifyVoipMsSend checked and VoIP.ms has no record of.
            $table->boolean('failed')->default(false)->after('unconfirmed');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropColumn('failed');
        });
    }
};
