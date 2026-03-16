<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            // Allow null so WO-staging pick tickets can be created without an
            // inventory allocation (they track materials needed, not allocated stock)
            $table->unsignedBigInteger('inventory_allocation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_allocation_id')->nullable(false)->change();
        });
    }
};
