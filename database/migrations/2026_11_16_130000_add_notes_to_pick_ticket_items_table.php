<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            // Note for the warehouse about this specific line (e.g. "not in inventory —
            // leftover jug labelled BINTING"). Lives on the line so removing the item
            // removes its note too.
            $table->text('notes')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
