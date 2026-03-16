<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receipts', function (Blueprint $table) {
            $table->id();

            // Source — may come from a PO item or be entered manually
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();

            // Catalog reference (nullable — stock PO items may not have a style)
            $table->unsignedBigInteger('product_style_id')->nullable();

            // Item snapshot
            $table->string('item_name');
            $table->string('unit')->default('');
            $table->decimal('quantity_received', 10, 2);
            $table->date('received_date');
            $table->text('notes')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->nullOnDelete();
            $table->foreign('product_style_id')->references('id')->on('product_styles')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['purchase_order_id']);
            $table->index(['purchase_order_item_id']);
            $table->index(['product_style_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receipts');
    }
};
