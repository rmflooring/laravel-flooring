<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->boolean('use_box_qty')->default(false)->after('units_per');
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->dropColumn('use_box_qty');
        });
    }
};
