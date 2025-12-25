<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('next_period_month')->nullable(); // January to December
            $table->string('filing_frequency')->nullable(); // Monthly, Quarterly, etc.
            $table->string('reporting_method')->nullable(); // Accrual, Cash
            $table->boolean('collect_on_sales')->default(false);
            $table->boolean('pay_on_purchases')->default(false);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_agencies');
    }
};
