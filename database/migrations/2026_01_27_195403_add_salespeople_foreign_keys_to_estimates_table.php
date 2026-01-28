<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('estimates', function (Blueprint $table) {
        $table->foreign('salesperson_1_id')
            ->references('id')->on('employees')
            ->nullOnDelete();

        $table->foreign('salesperson_2_id')
            ->references('id')->on('employees')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('estimates', function (Blueprint $table) {
        $table->dropForeign(['salesperson_1_id']);
        $table->dropForeign(['salesperson_2_id']);
    });
}
};
