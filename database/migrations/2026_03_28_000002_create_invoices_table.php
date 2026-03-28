<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();          // e.g. 2026-001
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->enum('status', [
                'draft', 'sent', 'paid', 'overdue', 'partially_paid', 'voided'
            ])->default('draft');
            $table->date('due_date')->nullable();
            $table->string('customer_po_number')->nullable();
            $table->text('notes')->nullable();

            // Totals (calculated from invoice items)
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            // Payment tracking
            $table->decimal('amount_paid', 10, 2)->default(0);

            // Lifecycle timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
