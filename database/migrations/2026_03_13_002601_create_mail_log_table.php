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
        Schema::create('mail_log', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->string('type')->default('system');  // rfm_notification, test, etc.
            $table->text('error')->nullable();           // Graph error message on failure
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_log');
    }
};
