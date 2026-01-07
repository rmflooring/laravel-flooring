<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();

            // Identity / numbering
            $table->string('estimate_number')->nullable(); // e.g. EST-2026-014-R1 (you can enforce later)
            $table->unsignedInteger('revision_no')->default(0); // 0=original, 1=R1, 2=R2...

            // Lifecycle
            $table->enum('status', ['draft', 'sent', 'revised', 'approved', 'rejected', 'void'])
                ->default('draft');

            // Basic job/customer snapshot (keep it simple for now)
            $table->string('customer_name')->nullable();
            $table->string('job_name')->nullable();
            $table->string('job_no')->nullable();
            $table->string('job_address')->nullable();
            $table->string('pm_name')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Totals (store what UI calculates)
            $table->decimal('subtotal_materials', 10, 2)->default(0.00);
            $table->decimal('subtotal_labour', 10, 2)->default(0.00);
            $table->decimal('subtotal_freight', 10, 2)->default(0.00);

            $table->decimal('pretax_total', 10, 2)->default(0.00);

            // Tax (Phase 3B, but columns now)
            $table->unsignedBigInteger('tax_group_id')->nullable();
            $table->decimal('tax_rate_percent', 6, 3)->default(0.000); // e.g. 12.000
            $table->decimal('tax_amount', 10, 2)->default(0.00);

            $table->decimal('grand_total', 10, 2)->default(0.00);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Helpful indexes
            $table->index(['status']);
            $table->index(['estimate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
