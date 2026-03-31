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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('sms_opted_out')->default(false)->after('mobile');
            $table->timestamp('sms_opted_out_at')->nullable()->after('sms_opted_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['sms_opted_out', 'sms_opted_out_at']);
        });
    }
};
