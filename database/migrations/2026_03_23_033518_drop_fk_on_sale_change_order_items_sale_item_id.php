<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The sale update deletes+recreates all items on every save, firing ON DELETE SET NULL
        // on sale_item_id every time and breaking CO delta tracking.
        // Drop the FK — keep sale_item_id as a plain nullable integer reference only.
        Schema::table('sale_change_order_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sale_change_order_items', function (Blueprint $table) {
            $table->foreign('sale_item_id')
                ->references('id')
                ->on('sale_items')
                ->nullOnDelete();
        });
    }
};
