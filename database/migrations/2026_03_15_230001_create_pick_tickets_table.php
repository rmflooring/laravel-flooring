<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pick_tickets', function (Blueprint $table) {
            $table->id();

            $table->string('pt_number')->unique();

            // Context — sale and optional work order
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('work_order_id')->nullable();

            // Lifecycle
            $table->string('status')->default('pending'); // pending | delivered | returned | cancelled
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->text('notes')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('work_order_id')->references('id')->on('work_orders')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['sale_id', 'status']);
            $table->index(['work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_tickets');
    }
};
