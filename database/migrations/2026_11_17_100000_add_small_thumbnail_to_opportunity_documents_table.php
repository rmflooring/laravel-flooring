<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunity_documents', function (Blueprint $table) {
            // ~300px thumbnail for the compact gallery view (thumbnail_path stays the ~600px one)
            $table->string('thumbnail_sm_path')->nullable()->after('thumbnail_path');
            // Set when an image couldn't be thumbnailed (e.g. HEIC) so the nightly run doesn't retry it forever
            $table->timestamp('thumbnail_failed_at')->nullable()->after('thumbnail_sm_path');
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_documents', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_sm_path', 'thumbnail_failed_at']);
        });
    }
};
