<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fetch all POs in creation order, with their linked sale number if any
        $pos = DB::table('purchase_orders')
            ->leftJoin('sales', 'purchase_orders.sale_id', '=', 'sales.id')
            ->orderBy('purchase_orders.id')
            ->select('purchase_orders.id', 'sales.sale_number')
            ->get();

        foreach ($pos as $index => $po) {
            $seq       = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $newNumber = $po->sale_number ? $seq . '-' . $po->sale_number : $seq;

            DB::table('purchase_orders')
                ->where('id', $po->id)
                ->update(['po_number' => $newNumber]);
        }
    }

    public function down(): void
    {
        // Restore old PO-YYYY-NNNN format, ordered by id
        $pos = DB::table('purchase_orders')
            ->orderBy('id')
            ->pluck('id');

        foreach ($pos as $index => $id) {
            $seq       = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $oldNumber = 'PO-' . now()->format('Y') . '-' . $seq;

            DB::table('purchase_orders')
                ->where('id', $id)
                ->update(['po_number' => $oldNumber]);
        }
    }
};
