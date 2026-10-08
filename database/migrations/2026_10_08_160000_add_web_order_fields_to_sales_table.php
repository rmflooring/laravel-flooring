<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Online orders from rmflooring.ca (paid by Stripe, picked up at the warehouse). See App\Services\WebOrderService. */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('channel', 20)->nullable()->after('is_quick_sale')->index();        // 'web' for online orders
            $table->string('web_order_number', 40)->nullable()->unique()->after('channel');    // the website's order number
            $table->string('web_status', 20)->nullable()->after('web_order_number')->index();   // new | confirmed | ready | picked_up | refunded
            $table->timestamp('web_status_at')->nullable()->after('web_status');
            $table->timestamp('web_ready_at')->nullable()->after('web_status_at');
            $table->timestamp('web_reminder_sent_at')->nullable()->after('web_ready_at');
            $table->string('web_payment_reference')->nullable()->after('web_reminder_sent_at'); // Stripe payment intent
            $table->json('web_order_data')->nullable()->after('web_payment_reference');        // original order (policies, boxes, pickup details)
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['web_order_number']);
            $table->dropIndex(['channel']);
            $table->dropIndex(['web_status']);
            $table->dropColumn(['channel', 'web_order_number', 'web_status', 'web_status_at', 'web_ready_at', 'web_reminder_sent_at', 'web_payment_reference', 'web_order_data']);
        });
    }
};
