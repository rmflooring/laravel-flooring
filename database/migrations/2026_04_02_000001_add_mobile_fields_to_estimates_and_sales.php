<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('homeowner_mobile')->nullable()->after('homeowner_phone');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('job_mobile')->nullable()->after('job_phone');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('homeowner_mobile');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('job_mobile');
        });
    }
};
