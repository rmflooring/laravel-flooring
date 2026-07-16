<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->enum('sender', ['agent', 'user']);
            $table->text('body');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('agent_tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
    }
};
