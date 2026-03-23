<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add sale_item_id to snapshot items (needed for delta matching)
        Schema::table('sale_change_order_items', function (Blueprint $table) {
            $table->foreignId('sale_item_id')
                ->nullable()
                ->after('sale_change_order_room_id')
                ->constrained('sale_items')
                ->nullOnDelete();
        });

        // 2. Add original sale totals + sent_at to change orders
        Schema::table('sale_change_orders', function (Blueprint $table) {
            $table->decimal('original_pretax_total', 10, 2)->default(0)->after('notes');
            $table->decimal('original_tax_amount', 10, 2)->default(0)->after('original_pretax_total');
            $table->decimal('original_grand_total', 10, 2)->default(0)->after('original_tax_amount');
            $table->timestamp('sent_at')->nullable()->after('approved_at');
        });

        // 3. Add change_in_progress to sales.status enum
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'open',
            'sent',
            'approved',
            'change_in_progress',
            'scheduled',
            'in_progress',
            'on_hold',
            'completed',
            'partially_invoiced',
            'invoiced',
            'cancelled'
        ) NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        Schema::table('sale_change_order_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->dropColumn('sale_item_id');
        });

        Schema::table('sale_change_orders', function (Blueprint $table) {
            $table->dropColumn(['original_pretax_total', 'original_tax_amount', 'original_grand_total', 'sent_at']);
        });

        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'open',
            'sent',
            'approved',
            'scheduled',
            'in_progress',
            'on_hold',
            'completed',
            'partially_invoiced',
            'invoiced',
            'cancelled'
        ) NOT NULL DEFAULT 'open'");
    }
};
