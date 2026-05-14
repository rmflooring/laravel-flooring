<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('shop_description');
            $table->boolean('shop_show_price')->default(false)->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'shop_show_price']);
        });
    }
};
