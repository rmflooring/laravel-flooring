<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_event_links', function (Blueprint $table) {
            // The original 2-column unique allowed the same Graph event to create a new
            // CalendarEvent row for each user on every sync cycle. Replace it with a
            // 3-column unique that scopes each link to a specific Microsoft account.
            // The (provider, external_event_id) unique from the original migration was never
            // applied to the live DB — external_event_id has no unique constraint at all.
            // Just add the correct per-account unique directly.
            $table->unique(['provider', 'microsoft_account_id', 'external_event_id'], 'eel_provider_account_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('external_event_links', function (Blueprint $table) {
            $table->dropUnique('eel_provider_account_event_unique');
        });
    }
};
