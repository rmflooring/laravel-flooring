<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('payer_type', ['parent', 'job_site'])->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->enum('payment_method', [
                'cash', 'cheque', 'e-transfer', 'credit_card', 'other',
            ])->default('e-transfer');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('sale_payment_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('sale_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropForeign(['sale_payment_id']);
            $table->dropColumn('sale_payment_id');
        });

        Schema::dropIfExists('sale_payments');
    }
};
