<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single payment received from a customer (one cheque, one e-transfer…) that is
 * split across several of their sales (as deposits) and/or invoices (as invoice
 * payments), with any unallocated remainder issued as a customer credit.
 *
 * The individual SalePayment / InvoicePayment / CustomerCredit rows stay the source
 * of truth for balances; they just point back here via customer_payment_id. In QBO
 * the whole thing is ONE Payment — see QboSyncService::pushCustomerPayment().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('qbo_id')->nullable()->index();
            $table->string('qbo_sync_token')->nullable();
            $table->timestamp('qbo_synced_at')->nullable();
            $table->text('qbo_last_error')->nullable();

            $table->timestamps();
        });

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->foreignId('customer_payment_id')->nullable()->after('sale_id')
                ->constrained('customer_payments')->nullOnDelete();
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('customer_payment_id')->nullable()->after('sale_payment_id')
                ->constrained('customer_payments')->nullOnDelete();
        });

        Schema::table('customer_credits', function (Blueprint $table) {
            $table->foreignId('source_customer_payment_id')->nullable()->after('source_invoice_id')
                ->constrained('customer_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credits', fn (Blueprint $t) => $t->dropConstrainedForeignId('source_customer_payment_id'));
        Schema::table('invoice_payments', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_payment_id'));
        Schema::table('sale_payments', fn (Blueprint $t) => $t->dropConstrainedForeignId('customer_payment_id'));
        Schema::dropIfExists('customer_payments');
    }
};
