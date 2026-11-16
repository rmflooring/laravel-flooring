<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Combined PO lines: the same material ordered for several rooms stays one PO
 * line per room internally (receiving, allocation, pick tickets, RTV and the
 * sale's ordered-qty tracking all key on sale_item_id), but lines sharing a
 * vendor_group are merged into a single line on the vendor's copy (PDF).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('vendor_group', 36)->nullable()->after('sale_item_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex(['vendor_group']);
            $table->dropColumn('vendor_group');
        });
    }
};
