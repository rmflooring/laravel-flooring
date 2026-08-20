<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            // Not unique — every sample in one checkout event shares the same number.
            $table->string('checkout_number')->nullable()->index()->after('checkout_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->dropColumn('checkout_number');
        });
    }
};
