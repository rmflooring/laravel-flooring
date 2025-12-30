<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_line_id')
                  ->constrained('product_lines')
                  ->onDelete('cascade');
            $table->string('name');
            $table->string('style_number')->nullable();
            $table->string('color')->nullable();
            $table->string('pattern')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            // Optional indexes for faster queries
            $table->index('product_line_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_styles');
    }
};
