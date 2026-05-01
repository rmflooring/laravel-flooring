<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->default('');
            $table->string('original_sale_number')->nullable();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_rate_groups')->nullOnDelete();
            $table->decimal('tax_rate_percent', 6, 3)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('refund_method');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quick_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quick_return_id')->constrained('quick_returns')->cascadeOnDelete();
            $table->foreignId('product_style_id')->nullable()->constrained('product_styles')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 4);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 4);
            $table->decimal('line_total', 10, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_return_items');
        Schema::dropIfExists('quick_returns');
    }
};
