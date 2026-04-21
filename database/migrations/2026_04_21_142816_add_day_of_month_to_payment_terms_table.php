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
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->unsignedTinyInteger('day_of_month')->nullable()->after('days');
        });

        DB::table('payment_terms')->where('name', 'Net 15 MF')->update(['day_of_month' => 15]);
        DB::table('payment_terms')->where('name', 'Net 20 MF')->update(['day_of_month' => 20]);
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('day_of_month');
        });
    }
};
