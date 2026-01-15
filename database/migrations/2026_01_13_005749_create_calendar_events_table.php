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
		Schema::create('calendar_events', function (Blueprint $table) {
			$table->id();

			// Who owns this event in your app
			$table->foreignId('owner_user_id')
				->constrained('users')
				->cascadeOnDelete();

			// Optional assignment (estimator/installer/etc.)
			$table->foreignId('assigned_to_user_id')
				->nullable()
				->constrained('users')
				->nullOnDelete();

			$table->string('title');
			$table->text('description')->nullable();
			$table->string('location')->nullable();

			$table->dateTime('starts_at');
			$table->dateTime('ends_at');
			$table->string('timezone')->default('America/Vancouver');

			// Later: link event to Opportunity / RFM / Job / etc.
			$table->nullableMorphs('related'); // related_type, related_id

			$table->string('status')->default('scheduled'); // scheduled|cancelled|completed etc.

			// Audit
			$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

			$table->timestamps();
			$table->softDeletes();

			$table->index(['owner_user_id', 'starts_at']);
			$table->index(['assigned_to_user_id', 'starts_at']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
