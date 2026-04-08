<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_set_id')->constrained('sample_sets')->cascadeOnDelete();
            $table->foreignId('product_style_id')->constrained('product_styles')->restrictOnDelete();
            $table->decimal('display_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_set_items');
    }
};
