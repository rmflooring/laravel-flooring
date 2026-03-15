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
        Schema::create('work_order_item_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_item_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->timestamps();

            $table->foreign('work_order_item_id')->references('id')->on('work_order_items')->cascadeOnDelete();
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_item_materials');
    }
};
