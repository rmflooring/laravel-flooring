<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            // Make sample_id nullable so a checkout can belong to a set instead
            $table->foreignId('sample_id')->nullable()->change();
            $table->foreignId('sample_set_id')->nullable()->after('sample_id')
                ->constrained('sample_sets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->dropForeign(['sample_set_id']);
            $table->dropColumn('sample_set_id');
            $table->foreignId('sample_id')->nullable(false)->change();
        });
    }
};
