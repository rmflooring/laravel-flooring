<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->boolean('shop_visible')->default(false)->after('status');
            $table->text('shop_description')->nullable()->after('shop_visible');
        });

        Schema::table('product_styles', function (Blueprint $table) {
            $table->boolean('shop_visible')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn(['shop_visible', 'shop_description']);
        });

        Schema::table('product_styles', function (Blueprint $table) {
            $table->dropColumn('shop_visible');
        });
    }
};
