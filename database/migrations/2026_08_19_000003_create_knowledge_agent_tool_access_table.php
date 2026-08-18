<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable role -> live-data-tool access matrix, admin-editable — same shape
        // as agent_notification_settings' per-task_type matrix. Absence of a row (or
        // allowed=false) means denied; the 'admin' role always bypasses this regardless
        // of what's stored here (checked in code, not represented as rows).
        Schema::create('knowledge_agent_tool_access', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('tool_name');
            $table->boolean('allowed')->default(false);
            $table->timestamps();

            $table->unique(['role', 'tool_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_agent_tool_access');
    }
};
