<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signing batches — several documents sent to the client in one email. Each
 * document is still its own signing request; they share a batch_uuid and are
 * signed in batch_position order (the thank-you page links to the next one).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signing_requests', function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->after('uuid')->index();
            $table->unsignedTinyInteger('batch_position')->nullable()->after('batch_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('document_signing_requests', function (Blueprint $table) {
            $table->dropIndex(['batch_uuid']);
            $table->dropColumn(['batch_uuid', 'batch_position']);
        });
    }
};
