<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_allocations', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_allocations', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });
    }
};
