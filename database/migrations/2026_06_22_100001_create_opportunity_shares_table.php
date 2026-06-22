<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('opportunity_share_documents', function (Blueprint $table) {
            $table->foreignId('share_id')->constrained('opportunity_shares')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('opportunity_documents')->cascadeOnDelete();
            $table->primary(['share_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_share_documents');
        Schema::dropIfExists('opportunity_shares');
    }
};
