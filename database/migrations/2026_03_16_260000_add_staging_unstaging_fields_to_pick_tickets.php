<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            // Notes added by the person staging (instructions for warehouse team)
            $table->text('staging_notes')->nullable()->after('notes');

            // Unstage audit trail
            $table->unsignedBigInteger('unstaged_by')->nullable()->after('staging_notes');
            $table->timestamp('unstaged_at')->nullable()->after('unstaged_by');
            $table->text('unstage_reason')->nullable()->after('unstaged_at');

            $table->foreign('unstaged_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pick_tickets', function (Blueprint $table) {
            $table->dropForeign(['unstaged_by']);
            $table->dropColumn(['staging_notes', 'unstaged_by', 'unstaged_at', 'unstage_reason']);
        });
    }
};
