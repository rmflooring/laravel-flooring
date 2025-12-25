<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable(); // Canadian provinces
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->string('vendor_type')->nullable(); // e.g., Flooring Supplier, Subcontractor
            $table->string('status')->default('active'); // active, inactive
            $table->string('account_number')->nullable();
            $table->string('terms')->nullable(); // e.g., Net 30, COD
            // We'll add these later:
            // $table->foreignId('vendor_rep_id')->nullable()->constrained('vendor_reps');
            // $table->foreignId('default_gl_account_id')->nullable()->constrained('gl_accounts');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
