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
        Schema::table('customer_return_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->foreign('sale_item_id')
                  ->references('id')
                  ->on('sale_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_return_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->foreign('sale_item_id')
                  ->references('id')
                  ->on('sale_items')
                  ->restrictOnDelete();
        });
    }
};
