<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_returns', function (Blueprint $table) {
            $table->id();
            $table->string('rfc_number')->unique();
            $table->foreignId('pick_ticket_id')->nullable()->nullOnDelete()->constrained('pick_tickets');
            $table->foreignId('sale_id')->nullable()->nullOnDelete()->constrained('sales');
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('received_date')->nullable();
            $table->string('received_by')->nullable();
            $table->foreignId('created_by')->nullable()->nullOnDelete()->constrained('users');
            $table->foreignId('updated_by')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_returns');
    }
};
