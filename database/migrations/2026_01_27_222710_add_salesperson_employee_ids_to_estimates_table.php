<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->unsignedBigInteger('salesperson_1_employee_id')->nullable()->after('salesperson_1_id');
            $table->unsignedBigInteger('salesperson_2_employee_id')->nullable()->after('salesperson_2_id');

            $table->foreign('salesperson_1_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('salesperson_2_employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['salesperson_1_employee_id']);
            $table->dropForeign(['salesperson_2_employee_id']);

            $table->dropColumn(['salesperson_1_employee_id', 'salesperson_2_employee_id']);
        });
    }
};