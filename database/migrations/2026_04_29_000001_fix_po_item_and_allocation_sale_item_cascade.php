<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // purchase_order_items.sale_item_id: CASCADE → SET NULL
        // Column is already nullable — only the FK rule changes.
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign('purchase_order_items_sale_item_id_foreign');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->nullOnDelete();
        });

        // inventory_allocations.sale_item_id: CASCADE → SET NULL (make nullable first)
        Schema::table('inventory_allocations', function (Blueprint $table) {
            $table->dropForeign('inventory_allocations_sale_item_id_foreign');
            $table->unsignedBigInteger('sale_item_id')->nullable()->change();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign('purchase_order_items_sale_item_id_foreign');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
        });

        Schema::table('inventory_allocations', function (Blueprint $table) {
            $table->dropForeign('inventory_allocations_sale_item_id_foreign');
            $table->unsignedBigInteger('sale_item_id')->nullable(false)->change();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
        });
    }
};
