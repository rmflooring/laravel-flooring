<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_queries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('question');
            // Names of tools Claude actually called for this query (search_knowledge_base,
            // get_work_order_status, etc.) — for spotting patterns in bad answers later.
            $table->json('tools_used')->nullable();
            $table->longText('answer')->nullable();
            // What the answer cites — knowledge_entry ids and/or which live record was
            // looked up — so staff can verify it, per the chat UI's source-of-truth display.
            $table->json('source_refs')->nullable();
            $table->enum('feedback', ['up', 'down'])->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_queries');
    }
};
