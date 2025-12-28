<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_items', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // Main labour description
            $table->text('notes')->nullable(); // Additional notes
            $table->decimal('cost', 10, 2)->default(0.00); // Cost price
            $table->decimal('sell', 10, 2)->default(0.00); // Sell price
            $table->foreignId('unit_measure_id')->constrained('unit_measures')->onDelete('cascade'); // Link to Unit Measures table
            $table->enum('status', ['Active', 'Inactive', 'Needs Update'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_items');
    }
};
