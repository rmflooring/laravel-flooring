<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_sets', function (Blueprint $table) {
            $table->id();
            $table->string('set_id')->unique();
            $table->foreignId('product_line_id')->constrained('product_lines')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'checked_out', 'discontinued', 'retired', 'lost'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('discontinued_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_sets');
    }
};
