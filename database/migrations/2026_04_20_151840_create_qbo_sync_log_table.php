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
        Schema::create('qbo_sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');         // bill, invoice, vendor, customer, payment
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('direction');            // push | pull
            $table->string('qbo_id')->nullable();   // QBO entity ID
            $table->string('status');               // success | error | skipped
            $table->text('message')->nullable();    // error message or notes
            $table->json('payload')->nullable();    // what we sent
            $table->json('response')->nullable();   // what QBO returned
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qbo_sync_log');
    }
};
