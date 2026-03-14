<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_line_id')->nullable()->after('product_type');
            $table->unsignedBigInteger('product_style_id')->nullable()->after('product_line_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_line_id')->nullable()->after('product_type');
            $table->unsignedBigInteger('product_style_id')->nullable()->after('product_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn(['product_line_id', 'product_style_id']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['product_line_id', 'product_style_id']);
        });
    }
};
