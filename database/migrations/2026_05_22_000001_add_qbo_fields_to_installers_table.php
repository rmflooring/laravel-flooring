<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installers', function (Blueprint $table) {
            $table->string('qbo_id')->nullable()->after('vendor_id');
            $table->string('qbo_sync_token')->nullable()->after('qbo_id');
            $table->timestamp('qbo_synced_at')->nullable()->after('qbo_sync_token');
        });
    }

    public function down(): void
    {
        Schema::table('installers', function (Blueprint $table) {
            $table->dropColumn(['qbo_id', 'qbo_sync_token', 'qbo_synced_at']);
        });
    }
};
