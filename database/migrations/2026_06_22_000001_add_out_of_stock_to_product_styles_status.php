<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE product_styles MODIFY COLUMN status ENUM('active','out_of_stock','inactive','dropped','archived') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE product_styles SET status = 'inactive' WHERE status = 'out_of_stock'");
        DB::statement("ALTER TABLE product_styles MODIFY COLUMN status ENUM('active','inactive','dropped','archived') NOT NULL DEFAULT 'active'");
    }
};
