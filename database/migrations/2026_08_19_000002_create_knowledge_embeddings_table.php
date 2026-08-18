<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('knowledge_entry_id');
            $table->text('chunk_text');
            // MariaDB here is 10.11 (no native VECTOR type — that needs 11.7+), so this is
            // a plain JSON float array, scored in PHP by KnowledgeSearchService. Revisit if
            // upgrading MariaDB ever becomes worthwhile for this specifically.
            $table->json('embedding');
            $table->timestamps();

            $table->foreign('knowledge_entry_id')->references('id')->on('knowledge_entries')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_embeddings');
    }
};
