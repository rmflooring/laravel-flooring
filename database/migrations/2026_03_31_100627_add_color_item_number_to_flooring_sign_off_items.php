<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('flooring_sign_off_items', function (Blueprint $table) {
            $table->string('color_item_number')->nullable()->after('product_description');
        });
    }

    public function down(): void
    {
        Schema::table('flooring_sign_off_items', function (Blueprint $table) {
            $table->dropColumn('color_item_number');
        });
    }
};
