<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->boolean('store_available')->default(true)->after('shop_show_price');
            $table->unsignedSmallInteger('store_qty')->default(1)->after('store_available');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn(['store_available', 'store_qty']);
        });
    }
};
