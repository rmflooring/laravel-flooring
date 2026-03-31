<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('special_flow', 50)->nullable()->after('needs_sale');
        });

        DB::table('document_templates')
            ->where('name', 'Flooring Selection Sign-Off')
            ->update(['special_flow' => 'flooring_sign_off', 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('special_flow');
        });
    }
};
