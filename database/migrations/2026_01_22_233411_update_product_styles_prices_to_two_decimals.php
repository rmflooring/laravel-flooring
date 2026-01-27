<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Round existing values safely before changing precision
        DB::statement('
            UPDATE product_styles
            SET
                sell_price = ROUND(sell_price, 2),
                cost_price = ROUND(cost_price, 2)
        ');

        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('sell_price', 10, 2)->nullable()->change();
            $table->decimal('cost_price', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('sell_price', 10, 4)->nullable()->change();
            $table->decimal('cost_price', 10, 4)->nullable()->change();
        });
    }
};
