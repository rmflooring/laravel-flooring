<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_calendar_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique();

            // Per-user visibility toggles (nullable = "use app default" later)
            $table->boolean('show_rfm')->nullable();
            $table->boolean('show_installations')->nullable();
            $table->boolean('show_warehouse')->nullable();
            $table->boolean('show_team')->nullable();

            // Optional overlay (personal calendar availability)
            $table->boolean('show_availability')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_calendar_preferences');
    }
};