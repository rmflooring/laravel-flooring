<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'online' for web orders paid by card on rmflooring.ca (Stripe)
        DB::statement("ALTER TABLE invoice_payments MODIFY COLUMN payment_method ENUM('cash','cheque','e-transfer','visa','mastercard','other','credit_card','online') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoice_payments MODIFY COLUMN payment_method ENUM('cash','cheque','e-transfer','visa','mastercard','other','credit_card') NOT NULL");
    }
};
