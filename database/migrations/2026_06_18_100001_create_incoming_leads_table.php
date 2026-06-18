<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_leads', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('coquitlamflooring.ca');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('sms_consent')->default(false);
            $table->string('service_type')->nullable();
            $table->string('project_type')->nullable();
            $table->string('area')->nullable();
            $table->string('timeline')->nullable();
            $table->text('message')->nullable();
            $table->string('referral_source')->nullable();
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->unsignedBigInteger('opportunity_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('denial_reason')->nullable();
            $table->timestamps();

            $table->foreign('opportunity_id')->references('id')->on('opportunities')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_leads');
    }
};
