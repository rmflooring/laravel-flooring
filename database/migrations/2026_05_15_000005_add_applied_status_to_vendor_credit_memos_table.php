<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vendor_credit_memos MODIFY COLUMN status ENUM('open','applied','voided') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("UPDATE vendor_credit_memos SET status = 'open' WHERE status = 'applied'");
        DB::statement("ALTER TABLE vendor_credit_memos MODIFY COLUMN status ENUM('open','voided') NOT NULL DEFAULT 'open'");
    }
};
