<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->decimal('returned_qty', 10, 2)->default(0)->after('delivered_qty');
        });
    }

    public function down(): void
    {
        Schema::table('pick_ticket_items', function (Blueprint $table) {
            $table->dropColumn('returned_qty');
        });
    }
};
