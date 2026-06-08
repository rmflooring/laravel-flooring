<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->date('scheduled_end_date')->nullable()->after('scheduled_time');
            $table->string('scheduled_end_time', 5)->nullable()->after('scheduled_end_date'); // HH:MM
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['scheduled_end_date', 'scheduled_end_time']);
        });
    }
};
