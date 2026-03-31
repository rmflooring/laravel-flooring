<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flooring_sign_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft | finalized
            $table->date('date');
            $table->string('customer_name')->default('');
            $table->string('job_no')->default('');
            $table->string('job_site_name')->default('');
            $table->text('job_site_address')->nullable();
            $table->string('job_site_phone', 50)->nullable();
            $table->string('job_site_email')->nullable();
            $table->string('pm_name')->nullable();
            $table->foreignId('condition_id')
                ->nullable()
                ->constrained('flooring_sign_off_conditions')
                ->nullOnDelete();
            $table->text('condition_text')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flooring_sign_offs');
    }
};
