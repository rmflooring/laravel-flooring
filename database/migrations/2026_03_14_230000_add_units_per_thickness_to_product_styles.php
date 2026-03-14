<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('units_per', 8, 2)->nullable()->after('sell_price');
            $table->decimal('thickness', 6, 2)->nullable()->after('units_per');
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->dropColumn(['units_per', 'thickness']);
        });
    }
};
