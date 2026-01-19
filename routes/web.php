<?php

use App\Http\Controllers\MicrosoftCalendarConnectController;
use App\Http\Controllers\Pages\Settings\Integrations\MicrosoftIntegrationController;
use App\Http\Controllers\UserCalendarPreferenceController;
use App\Http\Controllers\CalendarEventController;

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Pages\OpportunityController;
use App\Http\Controllers\Pages\JobSiteCustomerController;

use App\Http\Controllers\OpportunityDocumentController;
use App\Http\Controllers\Pages\OpportunityMediaController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorRepController;
use App\Http\Controllers\Admin\ProjectManagerController;
use App\Http\Controllers\Admin\LabourTypeController;
use App\Http\Controllers\Admin\LabourItemController;
use App\Http\Controllers\Admin\UnitMeasureController;
use App\Http\Controllers\Admin\CustomerTypeController;

use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\DetailTypeController;
use App\Http\Controllers\Admin\TaxAgencyController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\TaxGroupController;
use App\Http\Controllers\Admin\GLAccountController;

use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ProductLineController;
use App\Http\Controllers\Admin\ProductStyleController;

use App\Http\Controllers\Admin\EstimateController;
use App\Http\Controllers\Admin\EmployeeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Dashboard (authenticated)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// TEMP: Estimate UI mock preview
Route::get('/admin/estimates/mock-create', function () {
    $opportunityId = request('opportunity_id');

    $opportunity = null;

    if ($opportunityId) {
        $opportunity = \App\Models\Opportunity::with([
            'parentCustomer',
            'jobSiteCustomer',
            'projectManager',
        ])->find($opportunityId);
    }

    return view('admin.estimates.mock-create', [
        'opportunity' => $opportunity,
    ]);
})->middleware(['auth']);


/*
|--------------------------------------------------------------------------
| Admin Area
|--------------------------------------------------------------------------
| IMPORTANT:
| - Admin-only = protected by your custom 'admin' middleware
| - Operational (customers/vendors/products/labour) = protected by permissions (role_or_permission)
*/
Route::prefix('admin')
    ->middleware(['auth', 'verified'])
    ->name('admin.')
    ->group(function () {

        /*
        |----------------------------------------
        | Admin-only (true system administration)
        |----------------------------------------
        */
        Route::middleware(['admin'])->group(function () {

            Route::get('/settings', function () {
                return view('admin.settings');
            })->name('settings');

            Route::resource('users', UserController::class);
            Route::resource('roles', RoleController::class);

            Route::resource('account-types', AccountTypeController::class)->names('account_types');
            Route::resource('detail-types', DetailTypeController::class)->names('detail_types');
            Route::resource('gl-accounts', GLAccountController::class)->names('gl_accounts');
            Route::resource('tax-agencies', TaxAgencyController::class)->names('tax_agencies');
            Route::resource('tax-rates', TaxRateController::class)->names('tax_rates');
			Route::get('tax', function () {
				return view('admin.tax.index');
			})->name('tax.index');

            Route::resource('unit-measures', UnitMeasureController::class)->names('unit_measures');
            Route::resource('customer-types', CustomerTypeController::class)->names('customer_types');

            Route::prefix('ajax/gl-accounts')->group(function () {
                Route::get('detail-types', [GLAccountController::class, 'getDetailTypes'])
                    ->name('gl_accounts.detail_types');

                Route::get('parent-accounts', [GLAccountController::class, 'getParentAccounts'])
                    ->name('gl_accounts.parent_accounts');
            });

            // Estimates (admin-only for now)
            Route::get('estimates', [EstimateController::class, 'index'])->name('estimates.index');
            Route::post('estimates', [EstimateController::class, 'store'])->name('estimates.store');
            Route::get('estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
            Route::put('estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
        });

        /*
        |----------------------------------------
        | Operational Management (permission-based)
        |----------------------------------------
        */

        // Employees
        Route::resource('employees', EmployeeController::class)
            ->middleware('role_or_permission:admin|view employees')
            ->names([
                'index'   => 'employees.index',
                'create'  => 'employees.create',
                'store'   => 'employees.store',
                'show'    => 'employees.show',
                'edit'    => 'employees.edit',
                'update'  => 'employees.update',
                'destroy' => 'employees.destroy',
            ]);
		
		Route::patch('employees/{employee}/restore', [EmployeeController::class, 'restore'])
			->name('employees.restore')
			->middleware('role_or_permission:admin|edit employees');
		
        // Customers
        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware('role_or_permission:admin|view customers')
            ->name('customers.index');

        Route::get('customers/create', [CustomerController::class, 'create'])
            ->middleware('role_or_permission:admin|create customers')
            ->name('customers.create');

        Route::post('customers', [CustomerController::class, 'store'])
            ->middleware('role_or_permission:admin|create customers')
            ->name('customers.store');

        Route::get('customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('role_or_permission:admin|view customers')
            ->name('customers.show');

        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.edit');

        Route::put('customers/{customer}', [CustomerController::class, 'update'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.update');

        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('role_or_permission:admin|delete customers')
            ->name('customers.destroy');

        // Project Managers
        Route::resource('project-managers', ProjectManagerController::class)
            ->middleware('role_or_permission:admin|view project managers')
            ->names([
                'index'   => 'project_managers.index',
                'create'  => 'project_managers.create',
                'store'   => 'project_managers.store',
                'show'    => 'project_managers.show',
                'edit'    => 'project_managers.edit',
                'update'  => 'project_managers.update',
                'destroy' => 'project_managers.destroy',
            ]);

		// Tax Groups
		Route::get('tax-groups', [\App\Http\Controllers\Admin\TaxGroupController::class, 'index'])
			->middleware('role_or_permission:admin|view tax groups')
			->name('tax_groups.index');

		Route::get('tax-groups/create', [\App\Http\Controllers\Admin\TaxGroupController::class, 'create'])
			->middleware('role_or_permission:admin|create tax groups')
			->name('tax_groups.create');

		Route::post('tax-groups', [\App\Http\Controllers\Admin\TaxGroupController::class, 'store'])
			->middleware('role_or_permission:admin|create tax groups')
			->name('tax_groups.store');

		Route::get('tax-groups/{tax_group}', [\App\Http\Controllers\Admin\TaxGroupController::class, 'show'])
			->middleware('role_or_permission:admin|view tax groups')
			->name('tax_groups.show');

		Route::get('tax-groups/{tax_group}/edit', [\App\Http\Controllers\Admin\TaxGroupController::class, 'edit'])
			->middleware('role_or_permission:admin|edit tax groups')
			->name('tax_groups.edit');

		Route::put('tax-groups/{tax_group}', [\App\Http\Controllers\Admin\TaxGroupController::class, 'update'])
			->middleware('role_or_permission:admin|edit tax groups')
			->name('tax_groups.update');

		Route::delete('tax-groups/{tax_group}', [\App\Http\Controllers\Admin\TaxGroupController::class, 'destroy'])
			->middleware('role_or_permission:admin|delete tax groups')
			->name('tax_groups.destroy');
		
		Route::post('tax-groups/{tax_group}/restore', [TaxGroupController::class, 'restore'])
			->middleware('role_or_permission:admin|edit tax groups')
			->name('tax_groups.restore');

        // Vendors
        Route::resource('vendors', VendorController::class)
            ->middleware('role_or_permission:admin|view vendors')
            ->names([
                'index'   => 'vendors.index',
                'create'  => 'vendors.create',
                'store'   => 'vendors.store',
                'show'    => 'vendors.show',
                'edit'    => 'vendors.edit',
                'update'  => 'vendors.update',
                'destroy' => 'vendors.destroy',
            ]);

        Route::resource('vendor-reps', VendorRepController::class)
            ->middleware('role_or_permission:admin|view vendor reps')
            ->names([
                'index'   => 'vendor_reps.index',
                'create'  => 'vendor_reps.create',
                'store'   => 'vendor_reps.store',
                'show'    => 'vendor_reps.show',
                'edit'    => 'vendor_reps.edit',
                'update'  => 'vendor_reps.update',
                'destroy' => 'vendor_reps.destroy',
            ]);

        // Labour
        Route::resource('labour-types', LabourTypeController::class)
            ->middleware('role_or_permission:admin|view labour types')
            ->names([
                'index'   => 'labour_types.index',
                'create'  => 'labour_types.create',
                'store'   => 'labour_types.store',
                'show'    => 'labour_types.show',
                'edit'    => 'labour_types.edit',
                'update'  => 'labour_types.update',
                'destroy' => 'labour_types.destroy',
            ]);

        Route::resource('labour-items', LabourItemController::class)
            ->middleware('role_or_permission:admin|view labour items')
            ->names([
                'index'   => 'labour_items.index',
                'create'  => 'labour_items.create',
                'store'   => 'labour_items.store',
                'show'    => 'labour_items.show',
                'edit'    => 'labour_items.edit',
                'update'  => 'labour_items.update',
                'destroy' => 'labour_items.destroy',
            ]);

        // Products
        Route::resource('product-types', ProductTypeController::class)
            ->middleware('role_or_permission:admin|view product types')
            ->names([
                'index'   => 'product_types.index',
                'create'  => 'product_types.create',
                'store'   => 'product_types.store',
                'show'    => 'product_types.show',
                'edit'    => 'product_types.edit',
                'update'  => 'product_types.update',
                'destroy' => 'product_types.destroy',
            ]);

        Route::resource('product-lines', ProductLineController::class)
            ->middleware('role_or_permission:admin|view product lines')
            ->names([
                'index'   => 'product_lines.index',
                'create'  => 'product_lines.create',
                'store'   => 'product_lines.store',
                'show'    => 'product_lines.show',
                'edit'    => 'product_lines.edit',
                'update'  => 'product_lines.update',
                'destroy' => 'product_lines.destroy',
            ]);

        Route::resource('product-lines/{product_line}/product-styles', ProductStyleController::class)
            ->middleware('role_or_permission:admin|view product styles')
            ->names([
                'index'   => 'product_styles.index',
                'create'  => 'product_styles.create',
                'store'   => 'product_styles.store',
                'show'    => 'product_styles.show',
                'edit'    => 'product_styles.edit',
                'update'  => 'product_styles.update',
                'destroy' => 'product_styles.destroy',
            ])
            ->parameters(['product_line' => 'product_line']);
    });

/*
|--------------------------------------------------------------------------
| Staff Pages Area
|--------------------------------------------------------------------------
*/
Route::prefix('pages')
    ->middleware(['auth', 'verified'])
    ->name('pages.')
    ->group(function () {
        Route::resource('opportunities', OpportunityController::class);

        Route::post('job-sites', [JobSiteCustomerController::class, 'store'])
            ->name('job-sites.store');

        Route::get('customers/{customer}/project-managers', [OpportunityController::class, 'projectManagersForCustomer'])
            ->name('customers.project-managers');
		
		Route::get('settings/integrations/microsoft', [MicrosoftIntegrationController::class, 'index'])
			->middleware('permission:connect microsoft calendar')
			->name('settings.integrations.microsoft.index');
		Route::patch('settings/integrations/microsoft/calendars/{calendar}', [MicrosoftIntegrationController::class, 'updateCalendar'])
			->middleware('permission:connect microsoft calendar')
			->name('settings.integrations.microsoft.calendars.update');
		
		Route::middleware(['permission:connect microsoft calendar'])->group(function () {
    Route::get('settings/integrations/microsoft/connect', [MicrosoftCalendarConnectController::class, 'redirect'])
        ->name('microsoft.connect');

    Route::get('settings/integrations/microsoft/callback', [MicrosoftCalendarConnectController::class, 'callback'])
        ->name('microsoft.callback');

    Route::post('settings/integrations/microsoft/calendars/discover', [MicrosoftCalendarConnectController::class, 'discoverCalendars'])
        ->name('microsoft.calendars.discover');

    Route::post('settings/integrations/microsoft/sync-now', [MicrosoftCalendarConnectController::class, 'syncNow'])
        ->name('microsoft.syncNow');
});

		
		Route::get('calendar', function () {
    $microsoftCalendars = \App\Models\MicrosoftCalendar::query()
    ->where('is_enabled', 1)
    ->orderBy('name')
    ->get();

    return view('pages.calendar.index', compact('microsoftCalendars'));
})->name('calendar.index');
		
		Route::get('calendar/events', function () {
				$events = \App\Models\CalendarEvent::where('owner_user_id', auth()->id())
					->orderBy('starts_at')
					->limit(100)
					->get();

				return view('pages.calendar.events.index', compact('events'));
			})->name('calendar.events.index');
		
Route::get('calendar/events/feed', function (\Illuminate\Http\Request $request) {

    // Expect comma-separated Microsoft calendar IDs, e.g. ?calendar_ids=id1,id2
    $calendarIds = collect(explode(',', (string) $request->query('calendar_ids')))
        ->map(fn ($v) => trim($v))
        ->filter()
        ->values();

    $eventsQuery = \App\Models\CalendarEvent::query()
        ->where('owner_user_id', auth()->id())
        ->whereNull('deleted_at');

    // If calendar_ids provided, filter by external_event_links.external_calendar_id
    if ($calendarIds->count() > 0) {
        $eventsQuery->whereIn('id', function ($sub) use ($calendarIds) {
            $sub->select('calendar_event_id')
                ->from('external_event_links')
                ->whereIn('external_calendar_id', $calendarIds->all());
        });
		
		
    }

$events = $eventsQuery->get()->map(function ($e) {
    $isAllDay = $e->starts_at
        && $e->ends_at
        && $e->starts_at->format('H:i:s') === '00:00:00'
        && $e->ends_at->format('H:i:s') === '00:00:00'
        && $e->ends_at->greaterThan($e->starts_at);

    return [
        'id'    => $e->id,
        'title' => $e->title,
        'start' => $isAllDay
    ? optional($e->starts_at)->format('Y-m-d')
    : optional($e->starts_at)->toIso8601String(),

'end' => $isAllDay
    ? optional($e->ends_at)->format('Y-m-d')
    : optional($e->ends_at)->toIso8601String(),
        'allDay' => $isAllDay,
        'extendedProps' => [
            'location'    => $e->location,
            'description' => $e->description,
            'provider'    => $e->provider,
			'start_date'  => $isAllDay ? optional($e->starts_at)->format('Y-m-d') : null,
			'end_date'    => $isAllDay ? optional($e->ends_at)->format('Y-m-d') : null,
        ],
    ];
});

    return response()->json($events);
})->name('calendar.events.feed');

// Calendar Event CRUD (create / edit / delete)
Route::post('calendar/events', [CalendarEventController::class, 'store'])
    ->name('calendar.events.store');

Route::patch('calendar/events/{event}', [CalendarEventController::class, 'update'])
    ->name('calendar.events.update');

Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])
    ->name('calendar.events.destroy');
		        /*
        |--------------------------------------------------------------------------
        | Opportunity Documents
        |--------------------------------------------------------------------------
        */
        Route::prefix('opportunities/{opportunity}')->group(function () {
			
			Route::get('media', [OpportunityMediaController::class, 'index'])
				->name('opportunities.media.index');
			
			Route::get('documents', [OpportunityDocumentController::class, 'index'])
                ->name('opportunities.documents.index');

            Route::post('documents', [OpportunityDocumentController::class, 'store'])
                ->name('opportunities.documents.store');

			Route::delete('documents/bulk', [OpportunityDocumentController::class, 'bulkDestroy'])
    			->name('opportunities.documents.bulkDestroy');
			
			Route::post('documents/bulk-restore', [OpportunityDocumentController::class, 'bulkRestore'])
				->name('opportunities.documents.bulkRestore');
			
			Route::delete('documents/bulk-force', [OpportunityDocumentController::class, 'bulkForceDestroy'])
				->name('opportunities.documents.bulkForceDestroy')
				->middleware('role_or_permission:admin');
			
			Route::post('documents/{document}/restore', [OpportunityDocumentController::class, 'restore'])
				->name('opportunities.documents.restore');
			
            Route::delete('documents/{document}', [OpportunityDocumentController::class, 'destroy'])
                ->name('opportunities.documents.destroy');
			
			 Route::patch('documents/{document}', [OpportunityDocumentController::class, 'update'])
                ->name('opportunities.documents.update');

            Route::delete('documents/{document}/force', [OpportunityDocumentController::class, 'forceDestroy'])
                ->name('opportunities.documents.forceDestroy')
                ->middleware('role_or_permission:admin');
        });

    });

	


// Profile routes (authenticated)
Route::middleware('auth')->group(function () {

    // 🔹 User Calendar Preferences API
    Route::get('/api/user/calendar-preferences', [UserCalendarPreferenceController::class, 'show'])
        ->name('api.user.calendar_preferences.show');

    Route::post('/api/user/calendar-preferences', [UserCalendarPreferenceController::class, 'upsert'])
        ->name('api.user.calendar_preferences.upsert');

    // Existing profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
