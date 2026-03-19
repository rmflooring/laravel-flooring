<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receipt_id')->nullable()->nullOnDelete()->constrained('inventory_receipts');
            $table->enum('type', ['received', 'customer_return', 'return_to_vendor', 'fulfilled', 'adjustment']);
            $table->decimal('quantity', 10, 2); // positive or negative
            $table->nullableMorphs('reference');  // reference_type + reference_id (polymorphic)
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamps();

            $table->index(['inventory_receipt_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
