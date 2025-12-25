<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->unique();
            $table->string('name');
            $table->foreignId('account_type_id')->constrained('account_types')->onDelete('restrict');
            $table->foreignId('detail_type_id')->constrained('detail_types')->onDelete('restrict');
            $table->foreignId('parent_id')->nullable()->constrained('gl_accounts')->onDelete('set null'); // for sub-accounts
            // We'll add default_tax_code_id later when tax_codes table exists
            // $table->foreignId('default_tax_code_id')->nullable()->constrained('tax_codes')->onDelete('set null');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_accounts');
    }
};
