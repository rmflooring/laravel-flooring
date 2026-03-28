<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_room_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sale_item_id')->nullable(); // reference only — no FK
            $table->enum('item_type', ['material', 'labour', 'freight'])->default('material');
            $table->string('label');                          // human-readable name/description
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);   // snapshotted at invoice creation
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('tax_group_id')->nullable();
            $table->string('tax_group_name')->nullable();     // snapshot
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
