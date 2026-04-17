<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_log', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('error');
            $table->text('cc')->nullable()->after('body');
            $table->string('attachment_name')->nullable()->after('cc');
            $table->string('pdf_url')->nullable()->after('attachment_name');
            $table->unsignedBigInteger('related_id')->nullable()->after('pdf_url');
            $table->string('related_type')->nullable()->after('related_id');
        });
    }

    public function down(): void
    {
        Schema::table('mail_log', function (Blueprint $table) {
            $table->dropColumn(['body', 'cc', 'attachment_name', 'pdf_url', 'related_id', 'related_type']);
        });
    }
};
