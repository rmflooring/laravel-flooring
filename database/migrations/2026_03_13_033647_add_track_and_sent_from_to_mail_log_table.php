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
        Schema::table('mail_log', function (Blueprint $table) {
            // 1 = Track 1 shared mailbox, 2 = Track 2 per-user delegated token
            $table->tinyInteger('track')->default(1)->after('type');
            // The actual sender address used (shared mailbox or user's personal address)
            $table->string('sent_from')->nullable()->after('track');
        });
    }

    public function down(): void
    {
        Schema::table('mail_log', function (Blueprint $table) {
            $table->dropColumn(['track', 'sent_from']);
        });
    }
};
