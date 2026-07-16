<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('task_type')->unique();
            $table->boolean('admin_bcc_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_notification_settings');
    }
};
