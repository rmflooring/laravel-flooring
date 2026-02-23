<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // Optional baseline link to original estimate room
            $table->foreignId('source_estimate_room_id')->nullable()->constrained('estimate_rooms')->nullOnDelete();

            $table->string('room_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->decimal('subtotal_materials', 10, 2)->default(0);
            $table->decimal('subtotal_labour', 10, 2)->default(0);
            $table->decimal('subtotal_freight', 10, 2)->default(0);
            $table->decimal('room_total', 10, 2)->default(0);

            // Change flag (for UI highlighting)
            $table->boolean('is_changed')->default(false);

            $table->timestamps();

            // Useful index for ordering rooms within a sale
            $table->index(['sale_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_rooms');
    }
};
