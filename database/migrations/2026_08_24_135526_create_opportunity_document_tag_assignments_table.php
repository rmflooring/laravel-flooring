<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_document_tag_assignments', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('opportunity_documents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('opportunity_document_tags')->cascadeOnDelete();
            $table->primary(['document_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_document_tag_assignments');
    }
};
