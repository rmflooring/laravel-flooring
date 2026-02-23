<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('homeowner_name')->nullable()->after('job_address');
            $table->string('homeowner_phone')->nullable()->after('homeowner_name');
            $table->string('homeowner_email')->nullable()->after('homeowner_phone');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['homeowner_name', 'homeowner_phone', 'homeowner_email']);
        });
    }
};
