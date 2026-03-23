<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix pick_ticket_items.sale_item_id — DB has CASCADE, should be SET NULL
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->foreign('sale_item_id')
                  ->references('id')->on('sale_items')
                  ->nullOnDelete();
        });

        // Fix customer_return_items.pick_ticket_item_id — DB has RESTRICT, should be SET NULL
        Schema::table('customer_return_items', function (Blueprint $table) {
            $table->dropForeign(['pick_ticket_item_id']);
            $table->foreign('pick_ticket_item_id')
                  ->references('id')->on('pick_ticket_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->foreign('sale_item_id')
                  ->references('id')->on('sale_items')
                  ->cascadeOnDelete();
        });

        Schema::table('customer_return_items', function (Blueprint $table) {
            $table->dropForeign(['pick_ticket_item_id']);
            $table->foreign('pick_ticket_item_id')
                  ->references('id')->on('pick_ticket_items')
                  ->restrictOnDelete();
        });
    }
};
