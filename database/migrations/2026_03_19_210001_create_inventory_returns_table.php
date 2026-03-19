<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique(); // RTV-YYYY-0001
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('vendor_id')->constrained('vendors');  // denormalized from PO
            $table->enum('status', ['draft', 'shipped', 'resolved'])->default('draft');
            $table->enum('reason', ['wrong_item', 'damaged', 'overstock', 'cancelled_job']);
            $table->enum('outcome', ['pending', 'credit_note', 'replacement', 'refund'])->default('pending');
            $table->string('vendor_reference')->nullable(); // vendor's RMA / credit note number
            $table->text('notes')->nullable();
            $table->foreignId('returned_by_user_id')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_returns');
    }
};
