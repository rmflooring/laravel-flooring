<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
Schema::table('estimates', function (Blueprint $table) {
$table->unsignedBigInteger('salesperson_1_id')->nullable()->after('pm_name');
$table->unsignedBigInteger('salesperson_2_id')->nullable()->after('salesperson_1_id');


// Optional (recommended): add indexes
$table->index('salesperson_1_id');
$table->index('salesperson_2_id');
});
}


public function down(): void
{
Schema::table('estimates', function (Blueprint $table) {
$table->dropIndex(['salesperson_1_id']);
$table->dropIndex(['salesperson_2_id']);


$table->dropColumn(['salesperson_1_id', 'salesperson_2_id']);
});
}
};
