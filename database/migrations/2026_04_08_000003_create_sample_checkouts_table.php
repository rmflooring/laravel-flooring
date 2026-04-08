<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')->constrained('samples')->onDelete('restrict');
            $table->enum('checkout_type', ['customer', 'staff'])->default('customer');

            // Customer checkout fields
            $table->unsignedBigInteger('customer_id')->nullable(); // existing customer record
            $table->string('customer_name')->nullable();           // free-text for walk-ins
            $table->string('customer_phone')->nullable();          // for SMS reminders
            $table->string('customer_email')->nullable();          // for email reminders

            // Staff checkout fields
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destination')->nullable(); // e.g. "Job site: 123 Main St"

            $table->unsignedSmallInteger('qty_checked_out')->default(1);
            $table->foreignId('checked_out_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('checked_out_at');
            $table->date('due_back_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('return_notes')->nullable();

            // Reminder tracking
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            $table->timestamps();

            $table->index('sample_id');
            $table->index('returned_at');
            $table->index('due_back_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_checkouts');
    }
};
