<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_id')->unique(); // e.g. SMP-0001
            $table->foreignId('product_style_id')->constrained('product_styles')->onDelete('restrict');
            $table->enum('status', ['active', 'checked_out', 'discontinued', 'retired', 'lost'])->default('active');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('location')->nullable(); // e.g. "Showroom – Hardwood Wall"
            $table->decimal('display_price', 10, 4)->nullable(); // overrides product_style sell_price if set
            $table->text('notes')->nullable();
            $table->date('received_at')->nullable();
            $table->timestamp('discontinued_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('product_style_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
