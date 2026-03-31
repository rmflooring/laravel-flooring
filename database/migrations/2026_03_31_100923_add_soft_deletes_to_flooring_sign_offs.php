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
        Schema::table('flooring_sign_offs', function (Blueprint $table) {
            $table->softDeletes()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('flooring_sign_offs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
