<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_applications', function (Blueprint $table) {
            $table->enum('type', ['redemption', 'refund'])->default('redemption')->after('customer_credit_id');
            $table->string('refund_method')->nullable()->after('notes');
            $table->string('reference_number')->nullable()->after('refund_method');
            $table->string('qbo_id')->nullable()->after('reference_number');
            $table->string('qbo_sync_token')->nullable()->after('qbo_id');
            $table->timestamp('qbo_synced_at')->nullable()->after('qbo_sync_token');
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_applications', function (Blueprint $table) {
            $table->dropColumn(['type', 'refund_method', 'reference_number', 'qbo_id', 'qbo_sync_token', 'qbo_synced_at']);
        });
    }
};
