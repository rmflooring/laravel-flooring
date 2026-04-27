<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add visa + mastercard; keep credit_card for backward compatibility with existing records
        DB::statement("ALTER TABLE invoice_payments MODIFY COLUMN payment_method ENUM('cash','cheque','e-transfer','visa','mastercard','other','credit_card') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoice_payments MODIFY COLUMN payment_method ENUM('cash','cheque','e-transfer','credit_card','other') NOT NULL");
    }
};
