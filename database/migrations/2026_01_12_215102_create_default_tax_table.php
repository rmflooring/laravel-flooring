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
    Schema::create('default_tax', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('tax_rate_group_id');

        $table->unsignedBigInteger('updated_by')->nullable()->index();
        $table->timestamps();

        $table->foreign('tax_rate_group_id')
            ->references('id')
            ->on('tax_rate_groups')
            ->restrictOnDelete();

        // Enforce only ONE row in this table (id will always be 1)
        $table->unique('id');
    });
}

public function down(): void
{
    Schema::dropIfExists('default_tax');
}

};
