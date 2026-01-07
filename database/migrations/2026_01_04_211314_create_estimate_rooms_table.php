<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_rooms', function (Blueprint $table) {
            $table->id();

            // Relationship
            $table->foreignId('estimate_id')
                ->constrained('estimates')
                ->cascadeOnDelete();

            // Room identity / ordering
            $table->string('room_name')->nullable(); // e.g. Living Room, Bedroom 1
            $table->unsignedInteger('sort_order')->default(0);

            // Room subtotals (mirrors UI logic)
            $table->decimal('subtotal_materials', 10, 2)->default(0.00);
            $table->decimal('subtotal_labour', 10, 2)->default(0.00);
            $table->decimal('subtotal_freight', 10, 2)->default(0.00);
            $table->decimal('room_total', 10, 2)->default(0.00);

            $table->timestamps();

            // Helpful index
            $table->index(['estimate_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_rooms');
    }
};
