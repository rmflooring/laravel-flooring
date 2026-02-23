<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('sale_room_id')->constrained('sale_rooms')->cascadeOnDelete();

            // Baseline link to original estimate item (for compare / audit)
            $table->foreignId('source_estimate_item_id')->nullable()->constrained('estimate_items')->nullOnDelete();

            $table->enum('item_type', ['material', 'labour', 'freight'])->index();

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

            // Change + remove flags
            $table->boolean('is_changed')->default(false);

            $table->boolean('is_removed')->default(false);
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Helpful composite indexes
            $table->index(['sale_room_id', 'sort_order']);
            $table->index(['sale_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
