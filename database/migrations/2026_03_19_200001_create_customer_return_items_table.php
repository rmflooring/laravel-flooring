<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_return_id')->constrained('customer_returns')->cascadeOnDelete();
            $table->foreignId('pick_ticket_item_id')->nullable()->nullOnDelete()->constrained('pick_ticket_items');
            $table->foreignId('sale_item_id')->nullable()->nullOnDelete()->constrained('sale_items');
            $table->foreignId('inventory_receipt_id')->nullable()->nullOnDelete()->constrained('inventory_receipts');
            $table->string('item_name');
            $table->string('unit')->default('');
            $table->decimal('quantity_returned', 10, 2);
            $table->string('condition')->nullable(); // good, damaged, partial
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_return_items');
    }
};
