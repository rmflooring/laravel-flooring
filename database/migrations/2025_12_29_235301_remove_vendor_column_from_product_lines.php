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
        Schema::table('product_lines', function (Blueprint $table) {
            $table->dropColumn('vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_lines', function (Blueprint $table) {
            $table->string('vendor')->nullable()->after('product_type_id'); // optional: restore if rollback
        });
    }
};
