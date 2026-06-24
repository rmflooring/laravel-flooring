<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_requests', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->enum('sent_via', ['sms', 'email']);
            $table->tinyInteger('rating')->unsigned()->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_requests');
    }
};
