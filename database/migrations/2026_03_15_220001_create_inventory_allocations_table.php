<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('inventory_receipt_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('sale_id'); // denormalized for easy querying

            $table->decimal('quantity', 10, 2);
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamps();

            $table->foreign('inventory_receipt_id')->references('id')->on('inventory_receipts')->cascadeOnDelete();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('allocated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['inventory_receipt_id']);
            $table->index(['sale_item_id']);
            $table->index(['sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_allocations');
    }
};
