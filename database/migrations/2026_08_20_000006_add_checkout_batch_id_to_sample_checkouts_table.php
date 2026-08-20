<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->uuid('checkout_batch_id')->nullable()->after('sample_set_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('sample_checkouts', function (Blueprint $table) {
            $table->dropColumn('checkout_batch_id');
        });
    }
};
