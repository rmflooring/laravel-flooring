<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            // 'pickup' | 'delivery' — only set for sale-direct PTs (not WO-staged)
            $table->string('fulfillment_type')->nullable()->after('staging_notes');
        });
    }

    public function down(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            $table->dropColumn('fulfillment_type');
        });
    }
};
