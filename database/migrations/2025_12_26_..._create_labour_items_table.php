<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NOTE: this file's name says "create_labour_items_table" but it contains the
// add-labour_type_id-column ALTER. See the sibling file
// (add_labour_type_id_to_labour_items_table) for the matching explanation —
// the bodies were swapped, not the filenames, to avoid touching already-run
// migration history on the dev/live DBs.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            $table->foreignId('labour_type_id')->nullable()->constrained('labour_types')->onDelete('set null')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('labour_items', function (Blueprint $table) {
            $table->dropForeign(['labour_type_id']);
            $table->dropColumn('labour_type_id');
        });
    }
};
