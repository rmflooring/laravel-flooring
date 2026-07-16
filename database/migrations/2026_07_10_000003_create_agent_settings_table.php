<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->string('admin_notification_email')->nullable();
            $table->json('allowed_sender_domains')->nullable();
            $table->json('allowed_sender_addresses')->nullable();
            $table->unsignedInteger('rate_limit_per_sender_per_hour')->default(20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_settings');
    }
};
