<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('estimate_id')
                ->constrained('estimates')
                ->cascadeOnDelete();

            $table->foreignId('estimate_room_id')
                ->constrained('estimate_rooms')
                ->cascadeOnDelete();

            // Type of line item
            $table->enum('item_type', ['material', 'labour', 'freight']);

            // Shared fields
            $table->decimal('quantity', 10, 2)->default(0.00);
            $table->string('unit')->nullable();

            $table->decimal('sell_price', 10, 2)->default(0.00);
            $table->decimal('line_total', 10, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            // Material-specific (safe to be nullable)
            $table->string('product_type')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('style')->nullable();
            $table->string('color_item_number')->nullable();
            $table->string('po_notes')->nullable();

            // Labour-specific
            $table->string('labour_type')->nullable();
            $table->string('description')->nullable();

            // Freight-specific (uses description too, but keep explicit)
            $table->string('freight_description')->nullable();

            $table->timestamps();

            // Indexes to keep things fast
            $table->index(['estimate_id', 'estimate_room_id']);
            $table->index(['item_type']);
            $table->index(['estimate_room_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
