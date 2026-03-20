<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make purchase_order_id nullable on inventory_returns
        // (RTVs sourced from RFC stock have no PO)
        Schema::table('inventory_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->unsignedBigInteger('purchase_order_id')->nullable()->change();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });

        // Make purchase_order_item_id nullable on inventory_return_items
        // and add item_name/unit snapshot columns for non-PO items
        Schema::table('inventory_return_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_item_id']);
            $table->unsignedBigInteger('purchase_order_item_id')->nullable()->change();
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->nullOnDelete();

            $table->string('item_name')->nullable()->after('purchase_order_item_id');
            $table->string('unit')->nullable()->after('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_return_items', function (Blueprint $table) {
            $table->dropColumn(['item_name', 'unit']);
            $table->dropForeign(['purchase_order_item_id']);
            $table->unsignedBigInteger('purchase_order_item_id')->nullable(false)->change();
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items');
        });

        Schema::table('inventory_returns', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->unsignedBigInteger('purchase_order_id')->nullable(false)->change();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders');
        });
    }
};
