<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_change_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // Numbering
            $table->string('co_number')->nullable()->unique();

            // Status / approval
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'cancelled'])
                  ->default('draft')
                  ->index();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Info
            $table->string('title')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Totals (tax uses SALE tax rate/group)
            $table->decimal('subtotal_materials', 10, 2)->default(0);
            $table->decimal('subtotal_labour', 10, 2)->default(0);
            $table->decimal('subtotal_freight', 10, 2)->default(0);
            $table->decimal('pretax_total', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            // Lock snapshot (freeze on approval)
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('locked_pretax_total', 10, 2)->default(0);
            $table->decimal('locked_tax_amount', 10, 2)->default(0);
            $table->decimal('locked_grand_total', 10, 2)->default(0);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['sale_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_change_orders');
    }
};
