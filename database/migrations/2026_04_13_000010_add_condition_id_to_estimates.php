<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->unsignedBigInteger('condition_id')->nullable()->after('notes');
            $table->foreign('condition_id')->references('id')->on('conditions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['condition_id']);
            $table->dropColumn('condition_id');
        });
    }
};
