<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            // Estimate Excel (Xactimate-style) export: replaces the Description column
            // for labour rows of this item. Takes precedence over the labour type's override.
            $table->string('xa_description_override', 500)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            $table->dropColumn('xa_description_override');
        });
    }
};
