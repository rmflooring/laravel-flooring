<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE document_signing_requests
            MODIFY COLUMN document_type
            ENUM('flooring_selection', 'work_auth', 'opportunity_document') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE document_signing_requests
            MODIFY COLUMN document_type
            ENUM('flooring_selection', 'work_auth') NOT NULL
        ");
    }
};
