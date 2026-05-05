<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('customer_po', 255)->nullable()->after('status');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_po', 255)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('customer_po');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('customer_po');
        });
    }
};
