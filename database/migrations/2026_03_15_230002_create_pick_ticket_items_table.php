<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pick_ticket_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pick_ticket_id');
            $table->unsignedBigInteger('inventory_allocation_id');

            // Denormalized for display (snapshot at creation time)
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->string('item_name');
            $table->string('unit')->default('');
            $table->decimal('quantity', 10, 2);

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('pick_ticket_id')->references('id')->on('pick_tickets')->cascadeOnDelete();
            $table->foreign('inventory_allocation_id')->references('id')->on('inventory_allocations')->cascadeOnDelete();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->nullOnDelete();

            $table->index(['pick_ticket_id']);
            $table->index(['inventory_allocation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_ticket_items');
    }
};
