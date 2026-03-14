<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();

            $table->string('po_number')->unique();

            $table->enum('status', ['pending', 'ordered', 'received', 'cancelled'])->default('pending')->index();
            $table->string('vendor_order_number')->nullable();

            $table->date('expected_delivery_date')->nullable();

            $table->enum('fulfillment_method', ['delivery_site', 'delivery_warehouse', 'delivery_custom', 'pickup']);
            $table->text('delivery_address')->nullable();

            $table->text('special_instructions')->nullable();

            $table->foreignId('ordered_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->index(['sale_id', 'status']);
            $table->index(['opportunity_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
