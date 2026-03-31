<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flooring_sign_off_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sign_off_id')
                ->constrained('flooring_sign_offs')
                ->cascadeOnDelete();
            $table->string('room_name')->default('');
            $table->text('product_description')->nullable();
            $table->decimal('qty', 10, 2)->default(0);
            $table->string('unit', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flooring_sign_off_items');
    }
};
