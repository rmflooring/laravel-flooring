<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('collection')->constrained('unit_measures')->nullOnDelete();
            $table->decimal('width', 8, 2)->nullable()->after('unit_id')->comment('inches');
            $table->decimal('length', 8, 2)->nullable()->after('width')->comment('inches');
        });
    }

    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'width', 'length']);
        });
    }
};
