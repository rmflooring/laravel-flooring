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
    Schema::create('tax_rate_group_items', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('tax_rate_group_id');
        $table->unsignedBigInteger('tax_rate_id');

        $table->timestamps();

        $table->foreign('tax_rate_group_id')
            ->references('id')
            ->on('tax_rate_groups')
            ->cascadeOnDelete();

        $table->foreign('tax_rate_id')
            ->references('id')
            ->on('tax_rates')
            ->restrictOnDelete();

        // Prevent duplicates (same tax rate added twice to same group)
        $table->unique(['tax_rate_group_id', 'tax_rate_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('tax_rate_group_items');
}

};
