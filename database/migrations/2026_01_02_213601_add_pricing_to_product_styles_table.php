<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingToProductStylesTable extends Migration
{
    public function up(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 4)->nullable()->after('description');
            $table->decimal('sell_price', 10, 4)->nullable()->after('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'sell_price']);
        });
    }
}
