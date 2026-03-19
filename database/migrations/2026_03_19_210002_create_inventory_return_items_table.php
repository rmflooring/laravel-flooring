<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_return_id')->constrained('inventory_returns')->cascadeOnDelete();
            $table->foreignId('inventory_receipt_id')->constrained('inventory_receipts');
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
            $table->decimal('quantity_returned', 10, 2);
            $table->decimal('unit_cost', 10, 2)->default(0);   // copied from po_item.cost_price at time of return
            $table->decimal('line_total', 10, 2)->default(0);  // quantity_returned × unit_cost, stored
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_return_items');
    }
};
