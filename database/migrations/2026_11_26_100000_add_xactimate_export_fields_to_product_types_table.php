<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            // Used by the estimate Excel (Xactimate-style) export for material rows
            $table->string('xa_cat', 50)->nullable()->after('default_sell_gl_account_id');
            $table->string('xa_selector', 50)->nullable()->after('xa_cat');
            $table->string('xa_description_override', 500)->nullable()->after('xa_selector');
        });
    }

    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            $table->dropColumn(['xa_cat', 'xa_selector', 'xa_description_override']);
        });
    }
};
