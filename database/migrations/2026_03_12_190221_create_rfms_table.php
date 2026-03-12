<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfms', function (Blueprint $table) {
            $table->id();

            // Parent opportunity
            $table->foreignId('opportunity_id')
                  ->constrained('opportunities')
                  ->cascadeOnDelete();

            // Estimator assigned to this RFM
            $table->foreignId('estimator_id')
                  ->constrained('employees');

            // Customers copied from opportunity at creation time
            $table->foreignId('parent_customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            $table->foreignId('job_site_customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // Editable copy of the site address (pre-filled, user can override)
            $table->string('site_address')->nullable();

            // Measurement details
            $table->string('flooring_type');
            $table->dateTime('scheduled_at');
            $table->text('special_instructions')->nullable();

            // Status lifecycle: pending → confirmed → completed → cancelled
            $table->string('status')->default('pending');

            // Linked MS365 calendar event (set after Graph API call succeeds)
            $table->foreignId('microsoft_calendar_id')
                  ->nullable()
                  ->constrained('microsoft_calendars')
                  ->nullOnDelete();

            $table->foreignId('calendar_event_id')
                  ->nullable()
                  ->constrained('calendar_events')
                  ->nullOnDelete();

            // Audit fields
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfms');
    }
};
