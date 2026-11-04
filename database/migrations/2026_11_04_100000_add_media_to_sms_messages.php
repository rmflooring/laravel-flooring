<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // MMS pictures, stored on the local disk: [{path, mime, size}, ...]
            $table->json('media')->nullable()->after('body');
            // VoIP.ms's own message id — stops a re-sent callback or a backfill run
            // from recording the same inbound message twice.
            $table->string('voipms_id')->nullable()->after('twilio_sid')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex(['voipms_id']);
            $table->dropColumn(['media', 'voipms_id']);
        });
    }
};
