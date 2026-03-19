<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending','ordered','received','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert any delivered POs to received before shrinking the enum
        DB::statement("UPDATE purchase_orders SET status = 'received' WHERE status = 'delivered'");
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending','ordered','received','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
