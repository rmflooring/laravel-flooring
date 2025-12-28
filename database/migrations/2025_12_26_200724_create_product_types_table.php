<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Product Type Name
            $table->foreignId('ordered_by_unit_id')
                  ->constrained('unit_measures')
                  ->onDelete('restrict'); // Ordered By unit
            $table->foreignId('sold_by_unit_id')
                  ->constrained('unit_measures')
                  ->onDelete('restrict'); // Sold By unit
            $table->foreignId('default_cost_gl_account_id')
                  ->nullable()
                  ->constrained('gl_accounts')
                  ->onDelete('set null');
            $table->foreignId('default_sell_gl_account_id')
                  ->nullable()
                  ->constrained('gl_accounts')
                  ->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
