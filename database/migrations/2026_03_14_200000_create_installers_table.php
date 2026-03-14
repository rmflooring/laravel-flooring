<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installers', function (Blueprint $table) {
            $table->id();

            // Optional link to an existing subcontractor vendor
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            // Core contact info
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('province', 2)->nullable();
            $table->string('postal_code', 10)->nullable();

            // Financial / account info
            $table->string('account_number')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('terms')->nullable(); // e.g. Net 30, COD

            // GL accounts
            $table->foreignId('gl_cost_account_id')->nullable()->constrained('gl_accounts')->nullOnDelete();
            $table->foreignId('gl_sale_account_id')->nullable()->constrained('gl_accounts')->nullOnDelete();

            // Status & notes
            $table->string('status')->default('active'); // active | inactive
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installers');
    }
};
