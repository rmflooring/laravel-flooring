<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-template switch for "Request Signature" on generated opportunity documents.
 * Only templates that are meant to be signed (the Work Authorization Form) get it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->boolean('allows_esignature')->default(false)->after('needs_sale');
        });

        DB::table('document_templates')
            ->where('name', 'like', 'Work Authorization%')
            ->update(['allows_esignature' => true]);
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('allows_esignature');
        });
    }
};
