<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->decimal('default_sell_price', 10, 2)->default(0.00)->after('collection');
            $table->decimal('default_cost_price', 10, 2)->default(0.00)->after('default_sell_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn(['default_sell_price', 'default_cost_price']);
        });
    }
};
