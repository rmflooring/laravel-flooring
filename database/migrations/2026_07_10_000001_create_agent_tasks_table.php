<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['email', 'chat']);
            $table->string('requester_email')->nullable();
            $table->unsignedBigInteger('requester_user_id')->nullable();
            $table->longText('raw_content')->nullable();
            $table->json('attachments')->nullable();
            $table->text('extracted_intent')->nullable();
            $table->string('task_type')->nullable()->index();
            // 'queued' is a Module 1 addition (not in the original spec's status enum) —
            // rows start here at webhook-create time and the queued job moves them into
            // one of pending_clarification/pending_confirmation/completed/failed/ignored.
            $table->string('status')->default('queued')->index();
            $table->float('confidence_score')->nullable();
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->timestamps();

            $table->foreign('requester_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
