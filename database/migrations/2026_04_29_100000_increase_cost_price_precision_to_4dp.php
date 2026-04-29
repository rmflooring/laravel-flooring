<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->decimal('default_cost_price', 10, 4)->default(0.0000)->change();
        });

        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->decimal('default_cost_price', 10, 2)->default(0.00)->change();
        });

        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->nullable()->change();
        });
    }
};
