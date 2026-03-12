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
        Schema::table('rfms', function (Blueprint $table) {
            $table->string('site_city')->nullable()->after('site_address');
            $table->string('site_postal_code')->nullable()->after('site_city');
        });
    }

    public function down(): void
    {
        Schema::table('rfms', function (Blueprint $table) {
            $table->dropColumn(['site_city', 'site_postal_code']);
        });
    }
};
