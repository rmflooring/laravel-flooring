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
		Schema::create('external_subscriptions', function (Blueprint $table) {
			$table->id();

			$table->string('provider')->default('microsoft');

			$table->foreignId('microsoft_account_id')
				->constrained('microsoft_accounts')
				->cascadeOnDelete();

			$table->string('external_calendar_id');

			$table->string('subscription_id'); // Graph subscription id
			$table->timestamp('expires_at')->nullable();

			$table->timestamp('last_notified_at')->nullable();

			$table->timestamps();

			$table->unique(['provider', 'subscription_id'], 'ext_sub_provider_subid_unique');

			$table->unique(
				['provider', 'microsoft_account_id', 'external_calendar_id'],
				'ext_sub_acct_cal_unique'
			);

			$table->index(['expires_at']);
			$table->index(['microsoft_account_id']);
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_subscriptions');
    }
};
