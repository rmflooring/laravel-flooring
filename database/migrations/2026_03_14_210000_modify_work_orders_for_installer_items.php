<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Drop old user-assignment and free-text work_type
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn(['work_type', 'assigned_to_user_id']);

            // Add installer link and email sent timestamp
            $table->foreignId('installer_id')->nullable()->after('sale_id')
                ->constrained('installers')->nullOnDelete();

            $table->timestamp('sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['installer_id']);
            $table->dropColumn(['installer_id', 'sent_at']);
            $table->string('work_type')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
