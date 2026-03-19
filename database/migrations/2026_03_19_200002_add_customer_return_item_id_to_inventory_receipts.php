<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->foreignId('customer_return_item_id')
                ->nullable()
                ->nullOnDelete()
                ->constrained('customer_return_items')
                ->after('purchase_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->dropForeign(['customer_return_item_id']);
            $table->dropColumn('customer_return_item_id');
        });
    }
};
