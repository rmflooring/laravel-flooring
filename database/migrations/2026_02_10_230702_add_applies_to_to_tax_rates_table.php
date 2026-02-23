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
    Schema::table('tax_rates', function (Blueprint $table) {
        $table->string('applies_to', 20)
            ->default('all')
            ->after('sales_rate'); // put it near sales_rate since it affects sales tax calc
    });
}

public function down(): void
{
    Schema::table('tax_rates', function (Blueprint $table) {
        $table->dropColumn('applies_to');
    });
}

};
