<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sale_payments MODIFY payment_method ENUM('cash','cheque','e-transfer','visa','mastercard','other') NOT NULL DEFAULT 'e-transfer'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sale_payments SET payment_method = 'other' WHERE payment_method IN ('visa','mastercard')");
        DB::statement("ALTER TABLE sale_payments MODIFY payment_method ENUM('cash','cheque','e-transfer','credit_card','other') NOT NULL DEFAULT 'e-transfer'");
    }
};
