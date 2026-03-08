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
    Schema::table('estimate_items', function (Blueprint $table) {
        $table->decimal('cost_price', 10, 2)->default(0.00)->after('sell_price');
        $table->decimal('cost_total', 10, 2)->default(0.00)->after('cost_price');
    });
}

public function down(): void
{
    Schema::table('estimate_items', function (Blueprint $table) {
        $table->dropColumn(['cost_price', 'cost_total']);
    });
}
};
