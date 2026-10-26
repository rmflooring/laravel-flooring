<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            // Per-line tax override (used by additional charges, e.g. GST-only freight).
            // NULL rates = inherit the bill's gst_rate / pst_rate. Rates are snapshotted
            // so later edits to a tax group don't change existing bills.
            $table->foreignId('tax_rate_group_id')->nullable()->after('charge_type')
                ->constrained('tax_rate_groups')->nullOnDelete();
            $table->decimal('gst_rate', 5, 4)->nullable()->after('tax_rate_group_id');
            $table->decimal('pst_rate', 5, 4)->nullable()->after('gst_rate');
        });
    }

    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rate_group_id');
            $table->dropColumn(['gst_rate', 'pst_rate']);
        });
    }
};
