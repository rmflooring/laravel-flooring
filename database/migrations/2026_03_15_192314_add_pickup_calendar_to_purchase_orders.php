<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dateTime('pickup_at')->nullable()->after('delivery_address');
            $table->unsignedBigInteger('calendar_event_id')->nullable()->after('pickup_at');
            $table->foreign('calendar_event_id')->references('id')->on('calendar_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['calendar_event_id']);
            $table->dropColumn(['pickup_at', 'calendar_event_id']);
        });
    }
};
