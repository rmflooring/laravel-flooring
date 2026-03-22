<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'open',
            'sent',
            'approved',
            'scheduled',
            'in_progress',
            'on_hold',
            'completed',
            'partially_invoiced',
            'invoiced',
            'cancelled'
        ) NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM(
            'open',
            'approved',
            'scheduled',
            'in_progress',
            'on_hold',
            'completed',
            'partially_invoiced',
            'invoiced',
            'cancelled'
        ) NOT NULL DEFAULT 'open'");
    }
};
