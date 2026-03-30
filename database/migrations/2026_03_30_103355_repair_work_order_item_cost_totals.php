<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Repair work_order_items rows where cost_total was zeroed out by the
        // retroactive WO re-link pass (which fetched items with only id+item_name,
        // then called model->update(), firing the saving event with null quantity/cost_price).
        // Fix: recalculate cost_total = quantity * cost_price for any row where they don't match.
        DB::statement('
            UPDATE work_order_items
            SET cost_total = ROUND(quantity * cost_price, 2)
            WHERE cost_total <> ROUND(quantity * cost_price, 2)
        ');
    }

    public function down(): void
    {
        //
    }
};
