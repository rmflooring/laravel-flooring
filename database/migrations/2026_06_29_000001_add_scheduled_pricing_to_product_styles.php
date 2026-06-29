<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->date('price_change_date')->nullable()->after('sell_price');
            $table->decimal('pending_cost_price', 10, 4)->nullable()->after('price_change_date');
            $table->decimal('pending_sell_price', 10, 2)->nullable()->after('pending_cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->dropColumn(['price_change_date', 'pending_cost_price', 'pending_sell_price']);
        });
    }
};
