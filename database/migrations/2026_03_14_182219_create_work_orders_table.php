<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            $table->string('wo_number')->unique();
            $table->string('work_type');

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time', 5)->nullable(); // HH:MM

            $table->string('status')->default('created');

            // FK to local CalendarEvent record (same pattern as RFMs)
            $table->foreignId('calendar_event_id')
                ->nullable()
                ->constrained('calendar_events')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['sale_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
