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
		Schema::create('microsoft_calendars', function (Blueprint $table) {
			$table->id();

			$table->foreignId('microsoft_account_id')
				->constrained('microsoft_accounts')
				->cascadeOnDelete();

			$table->string('calendar_id'); // Graph calendar id
			$table->string('name')->nullable();
			$table->boolean('is_primary')->default(false);

			// user controls this
			$table->boolean('is_enabled')->default(false);

			$table->timestamps();

			$table->unique(['microsoft_account_id', 'calendar_id']);
			$table->index(['microsoft_account_id', 'is_enabled']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsoft_calendars');
    }
};
