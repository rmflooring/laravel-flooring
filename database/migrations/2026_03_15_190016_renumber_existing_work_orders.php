<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $wos = DB::table('work_orders')
            ->leftJoin('sales', 'work_orders.sale_id', '=', 'sales.id')
            ->orderBy('work_orders.id')
            ->select('work_orders.id', 'sales.sale_number')
            ->get();

        foreach ($wos as $index => $wo) {
            $seq      = (string) ($index + 1);
            $newNum   = $wo->sale_number ? $seq . '-' . $wo->sale_number : $seq;
            DB::table('work_orders')->where('id', $wo->id)->update(['wo_number' => $newNum]);
        }
    }

    public function down(): void
    {
        $wos = DB::table('work_orders')->orderBy('id')->pluck('id');

        foreach ($wos as $index => $id) {
            $oldNum = 'WO-' . now()->format('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            DB::table('work_orders')->where('id', $id)->update(['wo_number' => $oldNum]);
        }
    }
};
