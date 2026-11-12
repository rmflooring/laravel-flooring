<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual checkout lines — for a physical sample that hasn't been entered into
 * the system yet. Such rows have no sample_id / sample_set_id; the typed-in
 * description is all we know about the item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->string('manual_description')->nullable()->after('sample_set_id');
            $table->string('manual_details')->nullable()->after('manual_description');
        });
    }

    public function down(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->dropColumn(['manual_description', 'manual_details']);
        });
    }
};
