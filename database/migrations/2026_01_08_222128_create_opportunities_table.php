<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();

            // Links to customers table (parent + job site/sub customer)
            $table->foreignId('parent_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('job_site_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // PMs belong to a parent customer (customer_id), but opportunity points to the PM directly
            $table->foreignId('project_manager_id')
                ->nullable()
                ->constrained('project_managers')
                ->nullOnDelete();

            // Job / opportunity fields
            $table->string('job_no')->nullable(); // user-typed, NOT unique
            $table->string('status')->default('New');

            // Sales people (text for now, since employees module not built yet)
            $table->string('sales_person_1')->nullable();
            $table->string('sales_person_2')->nullable();

            // Who initiated (we can show this on the UI)
            $table->foreignId('initiated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Audit columns (matching your existing pattern)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
