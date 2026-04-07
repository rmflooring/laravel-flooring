<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();

            $table->enum('bill_type', ['vendor', 'installer']);

            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('installer_id')->nullable()->constrained('installers')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();

            $table->string('reference_number');          // vendor's/installer's invoice number
            $table->date('bill_date');
            $table->date('due_date')->nullable();

            $table->enum('status', ['draft', 'pending', 'approved', 'overdue', 'voided'])->default('draft');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('gst_rate', 5, 4)->default(0);   // e.g. 0.0500 = 5%
            $table->decimal('pst_rate', 5, 4)->default(0);   // e.g. 0.0700 = 7%
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('pst_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0); // gst + pst combined
            $table->decimal('grand_total', 10, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['bill_type', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['installer_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();

            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('work_order_item_id')->nullable()->constrained('work_order_items')->nullOnDelete();

            $table->string('item_name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('bill_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
