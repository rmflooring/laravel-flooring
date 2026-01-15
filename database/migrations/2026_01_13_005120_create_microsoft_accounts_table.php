<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('microsoft_accounts', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // Graph identity context
        $table->string('tenant_id')->nullable();
        $table->string('microsoft_user_id')->nullable(); // Graph /me id
        $table->string('email')->nullable();

        // Tokens (we’ll encrypt in the Model later)
        $table->text('access_token')->nullable();
        $table->text('refresh_token')->nullable();
        $table->timestamp('token_expires_at')->nullable();

        $table->boolean('is_connected')->default(false);
        $table->timestamp('connected_at')->nullable();
        $table->timestamp('disconnected_at')->nullable();

        $table->timestamps();

        $table->unique(['user_id']);
        $table->index(['microsoft_user_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('microsoft_accounts');
    }
};
