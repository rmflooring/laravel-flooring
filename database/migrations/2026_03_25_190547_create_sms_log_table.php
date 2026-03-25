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
        Schema::create('sms_log', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('from')->nullable();
            $table->text('body');
            $table->string('type')->nullable();          // e.g. wo_scheduled, rfm_booked, test
            $table->string('status')->default('sent');   // sent | failed
            $table->text('error')->nullable();
            $table->string('related_type')->nullable();  // e.g. App\Models\WorkOrder
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_log');
    }
};
