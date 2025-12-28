<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            $table->foreignId('labour_type_id')->nullable()->constrained('labour_types')->onDelete('set null')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            $table->dropForeign(['labour_type_id']);
            $table->dropColumn('labour_type_id');
        });
    }
};
