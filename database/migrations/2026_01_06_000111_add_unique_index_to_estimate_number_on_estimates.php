<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->unique('estimate_number', 'estimates_estimate_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropUnique('estimates_estimate_number_unique');
        });
    }
};
