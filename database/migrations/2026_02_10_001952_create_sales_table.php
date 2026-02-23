<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Relationships / references
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_estimate_id')->constrained('estimates')->restrictOnDelete();

            // Numbering
            $table->string('sale_number')->nullable()->unique();
            $table->string('source_estimate_number')->nullable()->index();

            // Status (job workflow)
            $table->enum('status', [
                'open',
                'scheduled',
                'in_progress',
                'on_hold',
                'completed',
                'partially_invoiced',
                'invoiced',
                'cancelled',
            ])->default('open')->index();

            // Job snapshot fields
            $table->string('customer_name')->nullable();
            $table->string('job_name')->nullable();
            $table->string('job_no')->nullable();
            $table->string('job_address')->nullable();
            $table->string('pm_name')->nullable();

            // Salespeople (recommended: employee IDs only)
            $table->foreignId('salesperson_1_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('salesperson_2_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();

            // Live totals (editable)
            $table->decimal('subtotal_materials', 10, 2)->default(0);
            $table->decimal('subtotal_labour', 10, 2)->default(0);
            $table->decimal('subtotal_freight', 10, 2)->default(0);
            $table->decimal('pretax_total', 10, 2)->default(0);

            $table->foreignId('tax_group_id')->nullable()->constrained('tax_rate_groups')->nullOnDelete();
            $table->decimal('tax_rate_percent', 6, 3)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            // Locking
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lock_reason')->nullable();

            // Locked totals snapshot (freeze on lock)
            $table->decimal('locked_subtotal_materials', 10, 2)->default(0);
            $table->decimal('locked_subtotal_labour', 10, 2)->default(0);
            $table->decimal('locked_subtotal_freight', 10, 2)->default(0);
            $table->decimal('locked_pretax_total', 10, 2)->default(0);

            $table->foreignId('locked_tax_group_id')->nullable()->constrained('tax_rate_groups')->nullOnDelete();
            $table->decimal('locked_tax_rate_percent', 6, 3)->default(0);
            $table->decimal('locked_tax_amount', 10, 2)->default(0);
            $table->decimal('locked_grand_total', 10, 2)->default(0);

            // Invoicing summary (supports progress invoicing + “fully invoiced” rule)
            $table->decimal('invoiced_total', 10, 2)->default(0);
            $table->timestamp('invoiced_at')->nullable();
            $table->boolean('is_fully_invoiced')->default(false);

            // CO rollups (to avoid heavy sums in UI)
            $table->decimal('approved_co_total', 10, 2)->default(0);
            $table->decimal('revised_contract_total', 10, 2)->default(0);

            // Change tracking flags
            $table->boolean('has_changes')->default(false);
            $table->timestamp('changed_at')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            // Standard audit columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Enforce 1:1 between estimate and sale
            $table->unique('source_estimate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

