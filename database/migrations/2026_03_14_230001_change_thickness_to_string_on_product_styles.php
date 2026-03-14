<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->string('thickness', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_styles', function (Blueprint $table) {
            $table->decimal('thickness', 6, 2)->nullable()->change();
        });
    }
};
