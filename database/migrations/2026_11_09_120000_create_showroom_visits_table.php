<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view showroom visits',
        'create showroom visits',
        'edit showroom visits',
    ];

    private const ROLES = ['admin', 'Manager', 'sales', 'reception', 'coordinator', 'estimator'];

    public function up(): void
    {
        Schema::create('showroom_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('host_employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('scheduled')->index();

            // Contact details used for notifications (prefilled from the customer, editable per visit)
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_sms')->default(true);

            // Public customer link (cancel / request change)
            $table->string('token', 64)->unique();

            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('reminder_day_before_sent_at')->nullable();
            $table->timestamp('reminder_morning_sent_at')->nullable();

            $table->timestamp('change_requested_at')->nullable();
            $table->text('change_request_preferred')->nullable();
            $table->text('change_request_note')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by', 20)->nullable(); // customer | staff
            $table->text('cancellation_reason')->nullable();

            $table->unsignedBigInteger('microsoft_calendar_id')->nullable();
            $table->unsignedBigInteger('calendar_event_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('scheduled_at');
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach (self::ROLES as $roleName) {
                Role::where('name', $roleName)->first()?->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('showroom_visits');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::whereIn('name', self::PERMISSIONS)->delete();
    }
};
