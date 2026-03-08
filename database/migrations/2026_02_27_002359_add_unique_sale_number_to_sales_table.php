<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MariaDB-safe: no LIMIT in SHOW INDEX
        $existing = DB::select("
            SHOW INDEX FROM `sales`
            WHERE Key_name = 'sales_sale_number_unique'
        ");

        if (!empty($existing)) {
            return; // already exists
        }

        // Drop any NON-unique index on sale_number (name may vary)
        $indexes = DB::select("SHOW INDEX FROM `sales` WHERE Column_name = 'sale_number'");

        foreach ($indexes as $idx) {
            if ($idx->Key_name !== 'PRIMARY' && (int)$idx->Non_unique === 1) {
                Schema::table('sales', function (Blueprint $table) use ($idx) {
                    $table->dropIndex($idx->Key_name);
                });
            }
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('sale_number', 'sales_sale_number_unique');
        });
    }

    public function down(): void
    {
        $existing = DB::select("
            SHOW INDEX FROM `sales`
            WHERE Key_name = 'sales_sale_number_unique'
        ");

        if (empty($existing)) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_sale_number_unique');
            $table->index('sale_number');
        });
    }
};