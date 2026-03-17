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
            $table->string('insurance_company')->nullable()->after('notes');
            $table->string('adjuster')->nullable()->after('insurance_company');
            $table->string('policy_number')->nullable()->after('adjuster');
            $table->string('claim_number')->nullable()->after('policy_number');
            $table->date('dol')->nullable()->after('claim_number'); // Date of Loss
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['insurance_company', 'adjuster', 'policy_number', 'claim_number', 'dol']);
        });
    }
};
