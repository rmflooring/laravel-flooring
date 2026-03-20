<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_return_items', function (Blueprint $table) {
            $table->foreignId('sale_item_id')->nullable()->nullOnDelete()->constrained('sale_items')->after('purchase_order_item_id');
            $table->boolean('apply_to_sale_cost')->default(false)->after('line_total');
            $table->decimal('credit_received', 10, 2)->nullable()->after('apply_to_sale_cost');
            $table->timestamp('cost_applied_at')->nullable()->after('credit_received');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_return_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_item_id');
            $table->dropColumn(['apply_to_sale_cost', 'credit_received', 'cost_applied_at']);
        });
    }
};
