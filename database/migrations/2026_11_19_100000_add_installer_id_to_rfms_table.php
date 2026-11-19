<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfms', function (Blueprint $table) {
            $table->foreignId('installer_id')
                ->nullable()
                ->after('estimator_id')
                ->constrained('installers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('installer_id');
        });
    }
};
