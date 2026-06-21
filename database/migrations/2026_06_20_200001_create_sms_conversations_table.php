<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique('phone');
            $table->index('last_message_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_conversations');
    }
};
