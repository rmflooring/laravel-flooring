<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing string values to JSON arrays before changing column type
        DB::table('rfms')->whereNotNull('flooring_type')->orderBy('id')->each(function ($rfm) {
            $current = $rfm->flooring_type;
            // Only convert if not already JSON
            if (!str_starts_with(trim($current), '[')) {
                DB::table('rfms')->where('id', $rfm->id)->update([
                    'flooring_type' => json_encode([$current]),
                ]);
            }
        });

        Schema::table('rfms', function (Blueprint $table) {
            $table->json('flooring_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rfms', function (Blueprint $table) {
            $table->string('flooring_type')->nullable()->change();
        });
    }
};
