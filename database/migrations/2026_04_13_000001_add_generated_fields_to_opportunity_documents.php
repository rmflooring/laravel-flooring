<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_documents', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('template_id')->constrained('sales')->nullOnDelete();
            $table->json('document_fields')->nullable()->after('sale_id');
            $table->longText('rendered_body')->nullable()->after('document_fields');
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_documents', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['sale_id', 'document_fields', 'rendered_body']);
        });
    }
};
