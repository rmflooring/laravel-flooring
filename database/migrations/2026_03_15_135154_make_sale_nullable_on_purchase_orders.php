<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['opportunity_id']);
            $table->dropIndex(['sale_id', 'status']);
            $table->dropIndex(['opportunity_id', 'status']);

            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->unsignedBigInteger('opportunity_id')->nullable()->change();

            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();

            $table->index(['sale_id', 'status']);
            $table->index(['opportunity_id', 'status']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);

            $table->unsignedBigInteger('sale_item_id')->nullable()->change();

            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['opportunity_id']);
            $table->dropIndex(['sale_id', 'status']);
            $table->dropIndex(['opportunity_id', 'status']);

            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
            $table->unsignedBigInteger('opportunity_id')->nullable(false)->change();

            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();

            $table->index(['sale_id', 'status']);
            $table->index(['opportunity_id', 'status']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);

            $table->unsignedBigInteger('sale_item_id')->nullable(false)->change();

            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
        });
    }
};
