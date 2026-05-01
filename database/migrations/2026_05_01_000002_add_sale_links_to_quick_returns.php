<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_returns', function (Blueprint $table) {
            $table->foreignId('sale_id')
                ->nullable()
                ->after('id')
                ->constrained('sales')
                ->nullOnDelete();
        });

        Schema::table('quick_return_items', function (Blueprint $table) {
            $table->foreignId('sale_item_id')
                ->nullable()
                ->after('quick_return_id')
                ->constrained('sale_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quick_return_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->dropColumn('sale_item_id');
        });

        Schema::table('quick_returns', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};
