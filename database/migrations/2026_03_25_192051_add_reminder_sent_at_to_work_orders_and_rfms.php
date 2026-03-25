<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('sms_reminder_sent_at')->nullable()->after('sent_at');
        });

        Schema::table('rfms', function (Blueprint $table) {
            $table->timestamp('sms_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('sms_reminder_sent_at');
        });

        Schema::table('rfms', function (Blueprint $table) {
            $table->dropColumn('sms_reminder_sent_at');
        });
    }
};
