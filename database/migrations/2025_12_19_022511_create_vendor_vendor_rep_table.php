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
    Schema::create('vendor_vendor_rep', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
        $table->foreignId('vendor_rep_id')->constrained('vendor_reps')->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_vendor_rep');
    }
};
