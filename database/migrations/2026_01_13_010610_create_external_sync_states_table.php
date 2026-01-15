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
		Schema::create('external_sync_states', function (Blueprint $table) {
			$table->id();

			$table->string('provider')->default('microsoft');

			$table->foreignId('microsoft_account_id')
				->constrained('microsoft_accounts')
				->cascadeOnDelete();

			// Since you chose "user-select calendars", we track per calendar
			$table->string('external_calendar_id');

			// Store the delta cursor/token
			$table->longText('delta_token')->nullable();

			$table->timestamp('last_synced_at')->nullable();
			$table->timestamps();

			$table->unique(
				['provider', 'microsoft_account_id', 'external_calendar_id'],
				'ext_sync_state_unique'
			);
			$table->index(['microsoft_account_id']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_sync_states');
    }
};
