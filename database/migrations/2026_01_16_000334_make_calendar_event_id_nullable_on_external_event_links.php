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
    Schema::table('external_event_links', function (Blueprint $table) {
        $table->unsignedBigInteger('calendar_event_id')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('external_event_links', function (Blueprint $table) {
        $table->unsignedBigInteger('calendar_event_id')->nullable(false)->change();
    });
}

};
