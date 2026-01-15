<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunity_documents', function (Blueprint $table) {
            $table->id();

            // Link to opportunity
            $table->foreignId('opportunity_id')
                ->constrained('opportunities')
                ->cascadeOnDelete();

            // Storage (local now, S3 later)
            $table->string('disk')->default('local');     // e.g. local, s3
            $table->string('path');                       // e.g. opportunities/123/abc.pdf

            // File metadata
            $table->string('original_name');
            $table->string('stored_name')->nullable();    // optional, if you rename on upload
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // Classification
            $table->string('category')->index();          // document | media (auto)
            $table->string('category_override')->nullable();

            // Labels: managed + free-text
            $table->foreignId('label_id')
                ->nullable()
                ->constrained('opportunity_document_labels')
                ->nullOnDelete();

            $table->string('label_text')->nullable();     // if user types their own
            $table->text('description')->nullable();      // inline editable

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // deleted_at = archived for everyone
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_documents');
    }
};
