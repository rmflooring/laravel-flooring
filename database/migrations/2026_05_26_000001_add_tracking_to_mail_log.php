<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mail_log', function (Blueprint $table) {
            $table->string('tracking_token', 36)->nullable()->unique()->after('related_type');
            $table->timestamp('opened_at')->nullable()->after('tracking_token');
        });
    }
    public function down(): void {
        Schema::table('mail_log', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn(['tracking_token', 'opened_at']);
        });
    }
};
