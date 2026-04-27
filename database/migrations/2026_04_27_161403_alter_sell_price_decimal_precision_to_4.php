<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['estimate_items', 'sale_items', 'invoice_items', 'sale_change_order_items'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('sell_price', 10, 4)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['estimate_items', 'sale_items', 'invoice_items', 'sale_change_order_items'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('sell_price', 10, 2)->default(0)->change();
            });
        }
    }
};
