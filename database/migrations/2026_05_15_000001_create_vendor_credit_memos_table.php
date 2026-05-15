<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_credit_memos', function (Blueprint $table) {
            $table->id();
            $table->string('credit_memo_number')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_return_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('gst_rate', 8, 6)->default(0);
            $table->decimal('pst_rate', 8, 6)->default(0);
            $table->boolean('tax_manual')->default(false);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('pst_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->enum('status', ['open', 'voided'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_credit_memos');
    }
};
