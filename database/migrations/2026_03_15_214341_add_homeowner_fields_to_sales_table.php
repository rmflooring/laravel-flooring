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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('homeowner_name')->nullable()->after('customer_name');
            $table->string('job_phone')->nullable()->after('homeowner_name');
            $table->string('job_email')->nullable()->after('job_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['homeowner_name', 'job_phone', 'job_email']);
        });
    }
};
