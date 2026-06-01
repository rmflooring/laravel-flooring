<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signing_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('document_type', ['flooring_selection', 'work_auth']);
            $table->unsignedBigInteger('document_id');
            $table->string('client_name');
            $table->string('client_email');
            $table->enum('status', ['pending', 'signed', 'expired', 'cancelled'])->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('sent_at');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('pending_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->enum('signature_type', ['drawn', 'typed'])->nullable();
            $table->json('audit_log')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signing_requests');
    }
};
