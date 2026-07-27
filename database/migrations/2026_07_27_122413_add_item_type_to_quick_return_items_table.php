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
        Schema::table('quick_return_items', function (Blueprint $table) {
            $table->enum('item_type', ['material', 'labour', 'freight'])->default('material')->after('sale_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quick_return_items', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });
    }
};
