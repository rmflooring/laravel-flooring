<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Product details for the rmflooring.ca shop filters (species, finish, AC rating, core, ...).
     * One JSON column on the line (fields depend on the product type, see App\Support\ShopAttributes)
     * and a colour tone per style.
     */
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->json('shop_attributes')->nullable()->after('shop_description');
        });

        Schema::table('product_styles', function (Blueprint $table) {
            $table->string('color_tone', 20)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', fn (Blueprint $table) => $table->dropColumn('shop_attributes'));
        Schema::table('product_styles', fn (Blueprint $table) => $table->dropColumn('color_tone'));
    }
};
