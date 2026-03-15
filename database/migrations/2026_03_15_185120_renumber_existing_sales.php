<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sales = DB::table('sales')->orderBy('id')->select('id', 'sale_number')->get();

        foreach ($sales as $index => $sale) {
            $newNumber = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $oldNumber = $sale->sale_number;

            // Update the sale number
            DB::table('sales')->where('id', $sale->id)->update(['sale_number' => $newNumber]);

            // Update any PO numbers that embed the old sale number (e.g. 0001-2026-0024 → 0001-0024)
            DB::table('purchase_orders')
                ->where('sale_id', $sale->id)
                ->get(['id', 'po_number'])
                ->each(function ($po) use ($oldNumber, $newNumber) {
                    $seq       = explode('-', $po->po_number)[0];
                    $newPoNum  = $seq . '-' . $newNumber;
                    DB::table('purchase_orders')->where('id', $po->id)->update(['po_number' => $newPoNum]);
                });
        }
    }

    public function down(): void
    {
        $sales = DB::table('sales')->orderBy('id')->select('id', 'sale_number')->get();

        foreach ($sales as $index => $sale) {
            $oldNumber = now()->format('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $curNumber = $sale->sale_number;

            DB::table('sales')->where('id', $sale->id)->update(['sale_number' => $oldNumber]);

            DB::table('purchase_orders')
                ->where('sale_id', $sale->id)
                ->get(['id', 'po_number'])
                ->each(function ($po) use ($curNumber, $oldNumber) {
                    $seq      = explode('-', $po->po_number)[0];
                    $newPoNum = $seq . '-' . $oldNumber;
                    DB::table('purchase_orders')->where('id', $po->id)->update(['po_number' => $newPoNum]);
                });
        }
    }
};
