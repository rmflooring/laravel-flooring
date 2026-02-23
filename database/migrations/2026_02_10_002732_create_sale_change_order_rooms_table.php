<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_change_order_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_change_order_id')
                ->constrained('sale_change_orders')
                ->cascadeOnDelete();

            // Optional mapping back to a sale room
            $table->foreignId('sale_room_id')
                ->nullable()
                ->constrained('sale_rooms')
                ->nullOnDelete();

            $table->string('room_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->decimal('subtotal_materials', 10, 2)->default(0);
            $table->decimal('subtotal_labour', 10, 2)->default(0);
            $table->decimal('subtotal_freight', 10, 2)->default(0);
            $table->decimal('room_total', 10, 2)->default(0);

            $table->timestamps();

            $table->index(['sale_change_order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_change_order_rooms');
    }
};
