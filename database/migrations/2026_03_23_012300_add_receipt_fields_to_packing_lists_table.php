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
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->string('received_by')->nullable()->after('notes');
            $table->date('received_date')->nullable()->after('received_by');
            $table->string('received_company')->nullable()->after('received_date');
        });
    }

    public function down(): void
    {
        Schema::table('packing_lists', function (Blueprint $table) {
            $table->dropColumn(['received_by', 'received_date', 'received_company']);
        });
    }
};
