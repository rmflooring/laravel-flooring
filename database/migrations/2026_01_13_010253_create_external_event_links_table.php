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
		Schema::create('external_event_links', function (Blueprint $table) {
			$table->id();

			$table->foreignId('calendar_event_id')
				->constrained('calendar_events')
				->cascadeOnDelete();

			$table->string('provider')->default('microsoft');

			$table->foreignId('microsoft_account_id')
				->constrained('microsoft_accounts')
				->cascadeOnDelete();

			// where the event lives in Outlook
			$table->string('external_calendar_id')->nullable(); // Graph calendar id
			$table->string('external_event_id');               // Graph event id

			$table->timestamp('last_synced_at')->nullable();
			$table->timestamps();

			// One internal event links to one external event per provider
			$table->unique(['calendar_event_id', 'provider']);

			// Each external event id should map to one row
			$table->unique(['provider', 'external_event_id']);

			$table->index(['microsoft_account_id']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_event_links');
    }
};
