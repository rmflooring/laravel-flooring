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
    Schema::create('tax_rate_groups', function (Blueprint $table) {
        $table->id();

        $table->string('name'); // we'll add unique in a later step if you want it
        $table->text('description')->nullable();
        $table->text('notes')->nullable();

        $table->unsignedBigInteger('created_by')->nullable()->index();
        $table->unsignedBigInteger('updated_by')->nullable()->index();

        $table->timestamps();
        $table->softDeletes();

        // Optional: If you want FK to users table, tell me your users table name first.
        // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        // $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::dropIfExists('tax_rate_groups');
}



};
