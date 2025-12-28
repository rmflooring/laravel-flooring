<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tax Name
            $table->text('description')->nullable(); // Tax Description
            $table->foreignId('tax_agency_id')->constrained('tax_agencies')->onDelete('cascade');
            $table->boolean('collect_on_sales')->default(false);
            $table->decimal('sales_rate', 8, 4)->nullable()->default(0); // e.g., 5.0000 for 5%
            $table->foreignId('sales_gl_account_id')->nullable()->constrained('gl_accounts')->onDelete('set null');
            $table->boolean('pay_on_purchases')->default(false);
            $table->decimal('purchase_rate', 8, 4)->nullable()->default(0);
            $table->foreignId('purchase_gl_account_id')->nullable()->constrained('gl_accounts')->onDelete('set null');
            $table->boolean('show_on_return_line')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
