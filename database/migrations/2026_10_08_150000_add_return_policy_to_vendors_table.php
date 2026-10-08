<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Return policy per vendor, shown to customers on rmflooring.ca for products from that vendor. */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('returns_accepted')->nullable()->after('terms');            // null = not set up yet
            $table->unsignedSmallInteger('return_days')->nullable()->after('returns_accepted');
            $table->string('return_condition')->nullable()->after('return_days');       // e.g. "Unopened, full boxes only"
            $table->decimal('restocking_fee_percent', 5, 2)->nullable()->after('return_condition');
            $table->boolean('special_orders_final_sale')->default(true)->after('restocking_fee_percent');
            $table->text('return_policy_notes')->nullable()->after('special_orders_final_sale');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['returns_accepted', 'return_days', 'return_condition', 'restocking_fee_percent', 'special_orders_final_sale', 'return_policy_notes']);
        });
    }
};
