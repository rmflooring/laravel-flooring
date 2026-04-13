<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->text('condition_body')->nullable()->after('condition_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('condition_body');
        });
    }
};
