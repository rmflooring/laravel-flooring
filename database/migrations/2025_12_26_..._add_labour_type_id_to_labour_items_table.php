<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NOTE: this file's name says "add_labour_type_id" but it contains the
// labour_items *create* migration. Its sibling file (create_labour_items_table)
// contains the add-column ALTER instead. Both filenames have a malformed
// literal "..." timestamp segment (predating this fix), and on the dev/live
// DBs both filenames are already recorded as run — renaming either file would
// make Laravel think they're brand-new migrations and re-run them, failing
// with "table/column already exists". Swapping the *bodies* instead (so
// create runs before alter on a truly fresh install, e.g. PHPUnit's sqlite
// DB) fixes the fresh-install ordering bug with zero risk to already-migrated
// environments, since "already ran" is tracked by filename only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_items', function (Blueprint $table) {
            $table->id();
            $table->string('description'); // Main labour description
            $table->text('notes')->nullable(); // Additional notes
            $table->decimal('cost', 10, 2)->default(0.00); // Cost price
            $table->decimal('sell', 10, 2)->default(0.00); // Sell price
            $table->foreignId('unit_measure_id')->constrained('unit_measures')->onDelete('cascade'); // Link to Unit Measures table
            $table->enum('status', ['Active', 'Inactive', 'Needs Update'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_items');
    }
};
