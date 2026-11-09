<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_leads', function (Blueprint $table) {
            // Visitor IP as reported by the website form (the API call itself comes from the web server)
            $table->string('ip_address', 45)->nullable()->after('city')->index();
        });
    }

    public function down(): void
    {
        Schema::table('incoming_leads', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn('ip_address');
        });
    }
};
