<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Strip leading zeros from sale numbers (e.g. "0008" → "8")
        DB::table('sales')->orderBy('id')->select('id', 'sale_number')->get()
            ->each(function ($sale) {
                $stripped = (string) (int) $sale->sale_number;
                if ($stripped !== $sale->sale_number) {
                    DB::table('sales')->where('id', $sale->id)->update(['sale_number' => $stripped]);
                }
            });

        // Strip leading zeros from PO numbers (e.g. "0001-0008" → "1-8", "0001" → "1")
        DB::table('purchase_orders')->orderBy('id')->select('id', 'po_number', 'sale_id')->get()
            ->each(function ($po) {
                $parts    = explode('-', $po->po_number, 2);
                $seq      = (string) (int) $parts[0];
                $newPoNum = isset($parts[1]) ? $seq . '-' . ((string) (int) $parts[1]) : $seq;

                if ($newPoNum !== $po->po_number) {
                    DB::table('purchase_orders')->where('id', $po->id)->update(['po_number' => $newPoNum]);
                }
            });
    }

    public function down(): void
    {
        // Restore 4-digit zero-padded sale numbers
        DB::table('sales')->orderBy('id')->select('id', 'sale_number')->get()
            ->each(function ($sale) {
                $padded = str_pad($sale->sale_number, 4, '0', STR_PAD_LEFT);
                DB::table('sales')->where('id', $sale->id)->update(['sale_number' => $padded]);
            });

        // Restore 4-digit zero-padded PO numbers
        DB::table('purchase_orders')->orderBy('id')->select('id', 'po_number')->get()
            ->each(function ($po) {
                $parts    = explode('-', $po->po_number, 2);
                $seq      = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
                $newPoNum = isset($parts[1]) ? $seq . '-' . str_pad($parts[1], 4, '0', STR_PAD_LEFT) : $seq;
                DB::table('purchase_orders')->where('id', $po->id)->update(['po_number' => $newPoNum]);
            });
    }
};
