<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MariaDB requires re-declaring the full enum to add a value
        DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('draft','pending','approved','paid','overdue','voided') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Move any paid bills back to approved before removing the value
        DB::statement("UPDATE bills SET status = 'approved' WHERE status = 'paid'");
        DB::statement("ALTER TABLE bills MODIFY COLUMN status ENUM('draft','pending','approved','overdue','voided') NOT NULL DEFAULT 'draft'");
    }
};
