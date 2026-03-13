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
        Schema::table('microsoft_accounts', function (Blueprint $table) {
            $table->boolean('mail_connected')->default(false)->after('is_connected');
            $table->timestamp('mail_connected_at')->nullable()->after('mail_connected');
        });
    }

    public function down(): void
    {
        Schema::table('microsoft_accounts', function (Blueprint $table) {
            $table->dropColumn(['mail_connected', 'mail_connected_at']);
        });
    }
};
