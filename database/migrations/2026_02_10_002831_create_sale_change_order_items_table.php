<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_change_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_change_order_id')
                ->constrained('sale_change_orders')
                ->cascadeOnDelete();

            $table->foreignId('sale_change_order_room_id')
                ->constrained('sale_change_order_rooms')
                ->cascadeOnDelete();

            $table->enum('item_type', ['material', 'labour', 'freight'])->index();

            // Credits supported by negative quantities (as agreed)
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();

            // Material fields
            $table->string('product_type')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('style')->nullable();
            $table->string('color_item_number')->nullable();
            $table->string('po_notes')->nullable();

            // Labour fields
            $table->string('labour_type')->nullable();
            $table->string('description')->nullable();

            // Freight fields
            $table->string('freight_description')->nullable();

            $table->timestamps();

            $table->index(['sale_change_order_room_id', 'sort_order'], 'scoi_room_sort_idx');
$table->index(['sale_change_order_id', 'item_type'], 'scoi_co_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_change_order_items');
    }
};
