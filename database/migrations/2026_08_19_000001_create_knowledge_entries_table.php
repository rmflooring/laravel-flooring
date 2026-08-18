<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Fixed allowlist (KnowledgeEntry::CATEGORIES), not a DB enum — matches this
            // agent module's existing convention (AttachImagesService::CATEGORIES etc.)
            // of a PHP-side allowlist over a plain string column.
            $table->string('category')->index();
            $table->longText('content');
            // Optional exact-value fields (a price, a markup %, etc.) that shouldn't be
            // left to freetext parsing out of `content`.
            $table->json('structured_data')->nullable();
            // Spatie role names allowed to see this entry. Checked directly at query time
            // in KnowledgeSearchService — the source of truth for per-entry visibility.
            $table->json('visible_to_roles');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_entries');
    }
};
