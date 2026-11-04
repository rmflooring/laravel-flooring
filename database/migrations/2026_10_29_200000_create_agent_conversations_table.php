<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A knowledge chat thread — groups a user's questions so each chat has its own
        // follow-up context, and old chats can be archived out of the way.
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'archived_at', 'updated_at']);
        });

        Schema::table('agent_queries', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('user_id')
                ->constrained('agent_conversations')->nullOnDelete();
        });

        // Existing questions predate conversations — file each user's history under one
        // "Earlier questions" chat so nothing disappears from the page.
        $users = DB::table('agent_queries')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->get();

        foreach ($users as $row) {
            $conversationId = DB::table('agent_conversations')->insertGetId([
                'user_id' => $row->user_id,
                'title' => 'Earlier questions',
                'created_at' => $row->first_at,
                'updated_at' => $row->last_at,
            ]);

            DB::table('agent_queries')
                ->where('user_id', $row->user_id)
                ->update(['conversation_id' => $conversationId]);
        }
    }

    public function down(): void
    {
        Schema::table('agent_queries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
        });

        Schema::dropIfExists('agent_conversations');
    }
};
