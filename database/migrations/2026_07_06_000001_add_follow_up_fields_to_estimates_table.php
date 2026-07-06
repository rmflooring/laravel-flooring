<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->timestamp('first_sent_at')->nullable()->after('status');
            $table->unsignedTinyInteger('follow_up_stage')->default(0)->after('first_sent_at');
            $table->boolean('follow_up_closed')->default(false)->after('follow_up_stage');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['first_sent_at', 'follow_up_stage', 'follow_up_closed']);
        });
    }
};
