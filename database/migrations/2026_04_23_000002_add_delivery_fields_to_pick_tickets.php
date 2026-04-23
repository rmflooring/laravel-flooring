<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('fulfillment_type');
            $table->string('delivery_time', 5)->nullable()->after('delivery_date'); // HH:MM
            $table->unsignedBigInteger('calendar_event_id')->nullable()->after('delivery_time');

            $table->foreign('calendar_event_id')->references('id')->on('calendar_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            $table->dropForeign(['calendar_event_id']);
            $table->dropColumn(['delivery_date', 'delivery_time', 'calendar_event_id']);
        });
    }
};
