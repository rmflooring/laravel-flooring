<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bill_to_name')->nullable()->after('notes');
            $table->string('bill_to_address')->nullable()->after('bill_to_name');
            $table->string('bill_to_email')->nullable()->after('bill_to_address');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['bill_to_name', 'bill_to_address', 'bill_to_email']);
        });
    }
};
