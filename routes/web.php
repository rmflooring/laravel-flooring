<?php

use App\Http\Controllers\TwilioSmsWebhookController;
use App\Http\Controllers\MicrosoftCalendarConnectController;
use App\Http\Controllers\Pages\Settings\Integrations\MicrosoftIntegrationController;
use App\Http\Controllers\UserCalendarPreferenceController;
use App\Http\Controllers\CalendarEventController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Pages\OpportunityController;
use App\Http\Controllers\Pages\JobSiteCustomerController;
use App\Http\Controllers\Pages\RfmController;

use App\Http\Controllers\OpportunityDocumentController;
use App\Http\Controllers\Pages\OpportunityMediaController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerContactController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorRepController;
use App\Http\Controllers\Admin\InstallerController;
use App\Http\Controllers\Admin\OpportunityDocumentLabelController;
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
use App\Http\Controllers\Admin\ProductStylePhotoController;
use App\Http\Controllers\Admin\ProductCatalogController;

use App\Http\Controllers\Admin\EstimateController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Api\EstimateLabourTypeController;
use App\Http\Controllers\Pages\PurchaseOrderController;
use App\Http\Controllers\Pages\SaleStatusController;
use App\Http\Controllers\Pages\WorkOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Twilio inbound SMS webhook — no auth, no CSRF
Route::post('/webhook/twilio/sms', [TwilioSmsWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.twilio.sms');

// Dashboard (authenticated)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Estimate API — tax group rate (used by estimate/sale edit JS)
Route::get('estimates/api/tax-groups/{tax_group}/rate', function (int $tax_group) {
    $rateCol = 'sales_rate';
    foreach (['tax_rate_sales', 'sales_rate'] as $candidate) {
        if (Schema::hasColumn('tax_rates', $candidate)) {
            $rateCol = $candidate;
            break;
        }
    }

    $taxes = \DB::table('tax_rate_group_items as tgi')
        ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
        ->where('tgi.tax_rate_group_id', $tax_group)
        ->selectRaw("tr.name as tax_name, tr.$rateCol as tax_rate_sales")
        ->get();

    $rate     = (float) $taxes->sum('tax_rate_sales');
    $group    = \DB::table('tax_rate_groups')->where('id', $tax_group)->first();
    $groupName = (string) (($group->name ?? $group->group_name ?? $group->groupName ?? '') ?: 'Tax');

    return response()->json([
        'group_id'         => $tax_group,
        'group_name'       => $groupName,
        'tax_rate_percent' => $rate,
        'taxes'            => $taxes,
    ]);
})->middleware(['auth'])->name('estimates.api.tax-groups.rate');
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

            Route::get('/settings/mail', [\App\Http\Controllers\Admin\MailSettingsController::class, 'index'])
                ->name('settings.mail');
            Route::post('/settings/mail', [\App\Http\Controllers\Admin\MailSettingsController::class, 'update'])
                ->name('settings.mail.update');
            Route::post('/settings/mail/test', [\App\Http\Controllers\Admin\MailSettingsController::class, 'testSend'])
                ->name('settings.mail.test');

            // Track 2 — per-user MS365 mail OAuth (admin-initiated on behalf of a user)
            Route::get('/settings/mail/connect/{user}', [\App\Http\Controllers\Admin\AdminMicrosoftMailConnectController::class, 'redirect'])
                ->name('settings.mail.connect');
            Route::get('/settings/mail/callback', [\App\Http\Controllers\Admin\AdminMicrosoftMailConnectController::class, 'callback'])
                ->name('settings.mail.callback');
            Route::post('/settings/mail/disconnect/{user}', [\App\Http\Controllers\Admin\AdminMicrosoftMailConnectController::class, 'disconnect'])
                ->name('settings.mail.disconnect');
            Route::post('/settings/mail/test-user/{user}', [\App\Http\Controllers\Admin\MailSettingsController::class, 'testUserSend'])
                ->name('settings.mail.test-user');

            // Branding
            Route::get('/settings/branding', [\App\Http\Controllers\Admin\BrandingController::class, 'show'])
                ->name('settings.branding');
            Route::put('/settings/branding', [\App\Http\Controllers\Admin\BrandingController::class, 'update'])
                ->name('settings.branding.update');
            Route::post('/settings/branding/logo', [\App\Http\Controllers\Admin\BrandingController::class, 'uploadLogo'])
                ->name('settings.branding.upload-logo');
            Route::delete('/settings/branding/logo', [\App\Http\Controllers\Admin\BrandingController::class, 'removeLogo'])
                ->name('settings.branding.remove-logo');

            // Email templates (system/admin)
            Route::get('/settings/email-templates', [\App\Http\Controllers\Admin\AdminEmailTemplateController::class, 'index'])
                ->name('settings.email-templates.index');
            Route::post('/settings/email-templates/{type}', [\App\Http\Controllers\Admin\AdminEmailTemplateController::class, 'save'])
                ->name('settings.email-templates.save');
            Route::delete('/settings/email-templates/{type}', [\App\Http\Controllers\Admin\AdminEmailTemplateController::class, 'reset'])
                ->name('settings.email-templates.reset');

            // Calendar entry templates (admin)
            Route::get('/settings/calendar-templates', [\App\Http\Controllers\Admin\AdminCalendarTemplateController::class, 'index'])
                ->name('settings.calendar-templates.index');
            Route::post('/settings/calendar-templates/{type}', [\App\Http\Controllers\Admin\AdminCalendarTemplateController::class, 'save'])
                ->name('settings.calendar-templates.save');
            Route::delete('/settings/calendar-templates/{type}', [\App\Http\Controllers\Admin\AdminCalendarTemplateController::class, 'reset'])
                ->name('settings.calendar-templates.reset');

            Route::get('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'index'])
                ->name('settings.sms');
            Route::post('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'update'])
                ->name('settings.sms.update');
            Route::post('/settings/sms/test', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'testSend'])
                ->name('settings.sms.test');

            Route::get('/settings/storage', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'index'])
                ->name('settings.storage');
            Route::put('/settings/storage', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'update'])
                ->name('settings.storage.update');
            Route::post('/settings/storage/test', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'test'])
                ->name('settings.storage.test');

            Route::get('/settings/label-printer', [\App\Http\Controllers\Admin\LabelPrinterController::class, 'index'])
                ->name('settings.label-printer');
            Route::put('/settings/label-printer', [\App\Http\Controllers\Admin\LabelPrinterController::class, 'update'])
                ->name('settings.label-printer.update');

            Route::get('/settings/sms-templates', [\App\Http\Controllers\Admin\AdminSmsTemplateController::class, 'index'])
                ->name('settings.sms-templates.index');
            Route::post('/settings/sms-templates/{type}', [\App\Http\Controllers\Admin\AdminSmsTemplateController::class, 'save'])
                ->name('settings.sms-templates.save');
            Route::delete('/settings/sms-templates/{type}', [\App\Http\Controllers\Admin\AdminSmsTemplateController::class, 'reset'])
                ->name('settings.sms-templates.reset');

            // QuickBooks Online
            Route::get('/settings/quickbooks', [\App\Http\Controllers\Admin\QuickBooksController::class, 'index'])
                ->name('settings.quickbooks');
            Route::get('/settings/quickbooks/connect', [\App\Http\Controllers\Admin\QuickBooksController::class, 'connect'])
                ->name('settings.quickbooks.connect');
            Route::get('/settings/quickbooks/callback', [\App\Http\Controllers\Admin\QuickBooksController::class, 'callback'])
                ->name('settings.quickbooks.callback');
            Route::post('/settings/quickbooks/disconnect', [\App\Http\Controllers\Admin\QuickBooksController::class, 'disconnect'])
                ->name('settings.quickbooks.disconnect');
            Route::post('/settings/quickbooks/settings', [\App\Http\Controllers\Admin\QuickBooksController::class, 'saveSettings'])
                ->name('settings.quickbooks.save-settings');

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

            //freight management
			Route::get('/freight-items', [\App\Http\Controllers\Admin\FreightItemController::class, 'index'])
			  ->name('freight_items.index');

			Route::post('/freight-items', [\App\Http\Controllers\Admin\FreightItemController::class, 'store'])
			  ->name('freight_items.store');

			Route::put('/freight-items/{freightItem}', [\App\Http\Controllers\Admin\FreightItemController::class, 'update'])
			  ->name('freight_items.update');

            // Payments (received)
            Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])
                ->name('payments.index');

            Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])
                ->name('payments.show');

            Route::get('/payments/{payment}/edit', [\App\Http\Controllers\Admin\PaymentController::class, 'edit'])
                ->name('payments.edit');

            Route::put('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'update'])
                ->name('payments.update');

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

        Route::post('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.deactivate');

        Route::patch('customers/{customer}/sms-opt-out', [CustomerController::class, 'toggleSmsOptOut'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.sms-opt-out.toggle');

        // Customer Contacts
        Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.contacts.store');

        Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.contacts.update');

        Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy'])
            ->middleware('role_or_permission:admin|edit customers')
            ->name('customers.contacts.destroy');

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

        Route::post('vendors/{vendor}/push-to-qbo', [\App\Http\Controllers\Admin\VendorController::class, 'pushToQbo'])
            ->name('vendors.push-to-qbo')
            ->middleware('role_or_permission:admin|edit vendors');

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

        // General Conditions (used in sign-offs, estimates, sales, etc.)
        Route::post('conditions/defaults', [\App\Http\Controllers\Admin\ConditionController::class, 'saveDefaults'])
            ->middleware('role_or_permission:admin')
            ->name('conditions.defaults');
        Route::resource('conditions', \App\Http\Controllers\Admin\ConditionController::class)
            ->middleware('role_or_permission:admin')
            ->names([
                'index'   => 'conditions.index',
                'store'   => 'conditions.store',
                'edit'    => 'conditions.edit',
                'update'  => 'conditions.update',
                'destroy' => 'conditions.destroy',
            ])
            ->except(['show', 'create']);

        // Document Templates
        Route::resource('document-templates', \App\Http\Controllers\Admin\DocumentTemplateController::class)
            ->middleware('role_or_permission:admin')
            ->names([
                'index'   => 'document-templates.index',
                'create'  => 'document-templates.create',
                'store'   => 'document-templates.store',
                'edit'    => 'document-templates.edit',
                'update'  => 'document-templates.update',
                'destroy' => 'document-templates.destroy',
            ])
            ->except(['show']);

        // Payment Terms
        Route::resource('payment-terms', \App\Http\Controllers\Admin\PaymentTermController::class)
            ->middleware('role_or_permission:admin')
            ->names([
                'index'   => 'payment-terms.index',
                'store'   => 'payment-terms.store',
                'edit'    => 'payment-terms.edit',
                'update'  => 'payment-terms.update',
                'destroy' => 'payment-terms.destroy',
            ])
            ->only(['index', 'store', 'edit', 'update', 'destroy']);

        // Opportunity Document Labels
        Route::resource('opportunity-document-labels', OpportunityDocumentLabelController::class)
            ->middleware('role_or_permission:admin|manage document labels')
            ->names([
                'index'   => 'opportunity_document_labels.index',
                'store'   => 'opportunity_document_labels.store',
                'edit'    => 'opportunity_document_labels.edit',
                'update'  => 'opportunity_document_labels.update',
                'destroy' => 'opportunity_document_labels.destroy',
            ])
            ->only(['index', 'store', 'edit', 'update', 'destroy']);

        // Accounts Payable — Bills
        Route::middleware('role_or_permission:admin|view bills')->group(function () {
            Route::get('bills/aging', [\App\Http\Controllers\Admin\BillController::class, 'aging'])
                ->name('bills.aging');
            Route::resource('bills', \App\Http\Controllers\Admin\BillController::class)
                ->names([
                    'index'   => 'bills.index',
                    'create'  => 'bills.create',
                    'store'   => 'bills.store',
                    'show'    => 'bills.show',
                    'edit'    => 'bills.edit',
                    'update'  => 'bills.update',
                    'destroy' => 'bills.destroy',
                ]);
            Route::post('bills/{bill}/void', [\App\Http\Controllers\Admin\BillController::class, 'void'])
                ->name('bills.void');
            Route::post('bills/{bill}/push-to-qbo', [\App\Http\Controllers\Admin\BillController::class, 'pushToQbo'])
                ->name('bills.push-to-qbo');
        });

        // Installers
        Route::resource('installers', InstallerController::class)
            ->middleware('role_or_permission:admin|view installers')
            ->names([
                'index'   => 'installers.index',
                'create'  => 'installers.create',
                'store'   => 'installers.store',
                'show'    => 'installers.show',
                'edit'    => 'installers.edit',
                'update'  => 'installers.update',
                'destroy' => 'installers.destroy',
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
        Route::post('labour-items/{id}/restore', [LabourItemController::class, 'restore'])
            ->middleware('role_or_permission:admin|delete labour items')
            ->name('labour_items.restore');
        Route::delete('labour-items/{id}/force', [LabourItemController::class, 'forceDestroy'])
            ->middleware('role_or_permission:admin')
            ->name('labour_items.force-destroy');

        // Products
        Route::get('products', [ProductCatalogController::class, 'index'])
            ->middleware('role_or_permission:admin|view product lines')
            ->name('products.index');

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
        Route::post('product-lines/{product_line}/archive', [ProductLineController::class, 'archive'])
            ->middleware('role_or_permission:admin|view product lines')
            ->name('product_lines.archive');
        Route::post('product-lines/{product_line}/unarchive', [ProductLineController::class, 'unarchive'])
            ->middleware('role_or_permission:admin|view product lines')
            ->name('product_lines.unarchive');

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

        Route::post('product-lines/{product_line}/product-styles/{style}/archive', [ProductStyleController::class, 'archive'])
            ->middleware('role_or_permission:admin|view product styles')
            ->name('product_styles.archive');
        Route::post('product-lines/{product_line}/product-styles/{style}/unarchive', [ProductStyleController::class, 'unarchive'])
            ->middleware('role_or_permission:admin|view product styles')
            ->name('product_styles.unarchive');

        Route::post('product-lines/{product_line}/product-styles/{style}/duplicate', [ProductStyleController::class, 'duplicate'])
            ->middleware('role_or_permission:admin|view product styles')
            ->name('product_styles.duplicate');

        // Style photos
        Route::post('product-lines/{product_line}/product-styles/{style}/photos', [ProductStylePhotoController::class, 'store'])
            ->middleware('role_or_permission:admin|edit product styles')
            ->name('product_styles.photos.store');
        Route::delete('product-lines/{product_line}/product-styles/{style}/photos/{photo}', [ProductStylePhotoController::class, 'destroy'])
            ->middleware('role_or_permission:admin|edit product styles')
            ->name('product_styles.photos.destroy');
        Route::post('product-lines/{product_line}/product-styles/{style}/photos/{photo}/primary', [ProductStylePhotoController::class, 'setPrimary'])
            ->middleware('role_or_permission:admin|edit product styles')
            ->name('product_styles.photos.primary');
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
        Route::post('opportunities/{opportunity}/deactivate', [OpportunityController::class, 'deactivate'])
            ->name('opportunities.deactivate');

		// Estimates (moved to pages)
		Route::get('estimates', [EstimateController::class, 'index'])
			->middleware('permission:create estimates')
			->name('estimates.index');

		Route::post('estimates', [EstimateController::class, 'store'])
			->middleware('permission:create estimates')
			->name('estimates.store');

		Route::get('estimates/create', function () {
			$opportunityId = request('opportunity_id');
			$opportunity = null;
			if ($opportunityId) {
				$opportunity = \App\Models\Opportunity::with([
					'parentCustomer',
					'jobSiteCustomer',
					'projectManager',
				])->find($opportunityId);
			}
			$employees = \App\Models\Employee::where('status', 'active')
				->orderBy('first_name')
				->get(['id', 'first_name']);
			$defaultTaxGroupId = \DB::table('default_tax')
				->orderByDesc('id')
				->value('tax_rate_group_id');
			$taxGroups = \DB::table('tax_rate_groups')
				->orderBy('name')
				->get();
			$conditions = \App\Models\Condition::where('is_active', true)->orderBy('sort_order')->orderBy('title')->get();
			$defaultConditionId = (int) \App\Models\Setting::get('default_estimate_condition_id', 0) ?: null;
			return view('admin.estimates.create', [
				'opportunity'        => $opportunity,
				'employees'          => $employees,
				'defaultTaxGroupId'  => $defaultTaxGroupId,
				'taxGroups'          => $taxGroups,
				'conditions'         => $conditions,
				'defaultConditionId' => $defaultConditionId,
			]);
		})->middleware('permission:create estimates')
		  ->name('estimates.create');

		Route::get('estimates/{estimate}/edit', [EstimateController::class, 'edit'])
			->middleware('permission:create estimates')
			->name('estimates.edit');

		Route::put('estimates/{estimate}', [EstimateController::class, 'update'])
			->middleware('permission:create estimates')
			->name('estimates.update');
		
		Route::post('estimates/{estimate}/make-revision', [EstimateController::class, 'makeRevision'])
			->middleware('permission:create estimates')
			->name('estimates.make-revision');

		Route::post('estimates/{estimate}/profits/save-costs', [EstimateController::class, 'saveProfitCosts'])
			->name('estimates.profits.save-costs');

		Route::get('estimates/{estimate}', [EstimateController::class, 'show'])
			->middleware('permission:view estimates')
			->name('estimates.show');

		Route::post('estimates/{estimate}/send-email', [EstimateController::class, 'sendEmail'])
			->middleware('permission:create estimates')
			->name('estimates.send-email');

		Route::get('estimates/{estimate}/pdf', [EstimateController::class, 'previewPdf'])
			->middleware('permission:view estimates')
			->name('estimates.pdf');

		Route::get('mail-log/{type}/{id}', [\App\Http\Controllers\Pages\MailLogController::class, 'latest'])
			->name('mail-log.latest');

		// Quick Sales (Cash & Carry) — static routes before {sale} wildcard
		Route::get('quick-sales/create', [\App\Http\Controllers\Pages\QuickSaleController::class, 'create'])
			->name('quick-sales.create')
			->middleware('role_or_permission:admin|create sales');

		Route::post('quick-sales', [\App\Http\Controllers\Pages\QuickSaleController::class, 'store'])
			->name('quick-sales.store')
			->middleware('role_or_permission:admin|create sales');

		Route::get('quick-sales/api/customers', [\App\Http\Controllers\Pages\QuickSaleController::class, 'searchCustomers'])
			->name('quick-sales.api.customers');

		Route::get('quick-sales/api/products', [\App\Http\Controllers\Pages\QuickSaleController::class, 'searchProducts'])
			->name('quick-sales.api.products');

		Route::get('quick-sales/{sale}', [\App\Http\Controllers\Pages\QuickSaleController::class, 'show'])
			->name('quick-sales.show')
			->middleware('role_or_permission:admin|view sales');

		Route::get('quick-sales/{sale}/receipt', [\App\Http\Controllers\Pages\QuickSaleController::class, 'receipt'])
			->name('quick-sales.receipt')
			->middleware('role_or_permission:admin|view sales');

		Route::get('sales/{sale}/pdf', [\App\Http\Controllers\Pages\SaleController::class, 'previewPdf'])
			->name('sales.pdf');

		Route::post('sales/{sale}/profits/save-costs', [\App\Http\Controllers\Pages\SaleController::class, 'saveProfitCosts'])
			->name('sales.profits.save-costs');
		
		Route::get('estimates/{estimate}/profits', [EstimateController::class, 'showProfits'])
			->name('estimates.profits.show');

		Route::get('sales/{sale}/profits', [\App\Http\Controllers\Pages\SaleController::class, 'showProfits'])
			->name('sales.profits.show');

		// Estimate API endpoints (used by estimate + sale edit pages)
		Route::prefix('estimates/api')
			->middleware('permission:create estimates')
			->group(function () {
				Route::get('product-types', [EstimateController::class, 'apiProductTypes'])
					->name('estimates.api.product-types');
				Route::get('manufacturers', [EstimateController::class, 'apiManufacturers'])
					->name('estimates.api.manufacturers');
				Route::get('product-lines', [EstimateController::class, 'apiProductLines'])
					->name('estimates.api.product-lines');
				Route::get('styles', [EstimateController::class, 'apiStyles'])
					->name('estimates.api.styles');
				Route::get('product-lines/{product_line}/product-styles', [\App\Http\Controllers\Admin\ProductStyleController::class, 'index'])
					->name('estimates.api.product-styles');
				Route::get('labour-types', [\App\Http\Controllers\Api\EstimateLabourTypeController::class, 'index'])
					->name('estimates.api.labour-types');
				Route::get('labour-items', [\App\Http\Controllers\Api\EstimateLabourItemController::class, 'index'])
					->name('estimates.api.labour-items');
				Route::get('freight-items', [\App\Http\Controllers\Admin\FreightItemController::class, 'apiIndex'])
					->name('estimates.api.freight-items');
			});
		
		// Convert Estimate -> Sale
		Route::post('estimates/{estimate}/convert-to-sale', [EstimateController::class, 'convertToSale'])
			->middleware('permission:create estimates')
			->name('estimates.convert-to-sale');

		Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])
			->middleware('permission:create estimates')
			->name('estimates.destroy');

		Route::get('sales/{sale}/edit', [\App\Http\Controllers\Pages\SaleController::class, 'edit'])
  ->name('sales.edit');
		
		Route::put('sales/{sale}', [\App\Http\Controllers\Pages\SaleController::class, 'update'])
  ->name('sales.update');

		Route::delete('sales/{sale}', [\App\Http\Controllers\Pages\SaleController::class, 'destroy'])
			->middleware('role_or_permission:admin|delete sales')
			->name('sales.destroy');

		Route::delete('sales/{sale}/force', [\App\Http\Controllers\Pages\SaleController::class, 'forceDestroy'])
			->withTrashed()
			->middleware('role:admin')
			->name('sales.force-destroy');

		Route::post('sales/{sale}/restore', [\App\Http\Controllers\Pages\SaleController::class, 'restore'])
			->withTrashed()
			->middleware('role:admin')
			->name('sales.restore');

		Route::get('sales', [\App\Http\Controllers\Pages\SaleController::class, 'index'])
    ->name('sales.index');

		Route::get('sales/{sale}/status', [\App\Http\Controllers\Pages\SaleStatusController::class, 'show'])
			->name('sales.status')
			->middleware('role_or_permission:admin|view sale status');

		Route::post('sales/{sale}/sale-items/{saleItem}/inventory-allocations', [\App\Http\Controllers\Pages\InventoryAllocationController::class, 'store'])
			->name('sales.inventory-allocations.store')
			->middleware('role_or_permission:admin|view sale status');

		Route::get('sales/{sale}', [\App\Http\Controllers\Pages\SaleController::class, 'show'])
    ->name('sales.show');

		Route::post('sales/{sale}/send-email', [\App\Http\Controllers\Pages\SaleController::class, 'sendEmail'])
			->name('sales.send-email');

		// Work Orders — nested under a sale
		Route::get('sales/{sale}/work-orders/create', [\App\Http\Controllers\Pages\WorkOrderController::class, 'create'])
			->name('sales.work-orders.create')
			->middleware('role_or_permission:admin|create work orders');

		Route::post('sales/{sale}/work-orders', [\App\Http\Controllers\Pages\WorkOrderController::class, 'store'])
			->name('sales.work-orders.store')
			->middleware('role_or_permission:admin|create work orders');

		Route::get('sales/{sale}/work-orders/{workOrder}', [\App\Http\Controllers\Pages\WorkOrderController::class, 'show'])
			->name('sales.work-orders.show')
			->middleware('role_or_permission:admin|view work orders');

		Route::get('sales/{sale}/work-orders/{workOrder}/edit', [\App\Http\Controllers\Pages\WorkOrderController::class, 'edit'])
			->name('sales.work-orders.edit')
			->middleware('role_or_permission:admin|edit work orders');

		Route::put('sales/{sale}/work-orders/{workOrder}', [\App\Http\Controllers\Pages\WorkOrderController::class, 'update'])
			->name('sales.work-orders.update')
			->middleware('role_or_permission:admin|edit work orders');

		Route::delete('sales/{sale}/work-orders/{workOrder}', [\App\Http\Controllers\Pages\WorkOrderController::class, 'destroy'])
			->name('sales.work-orders.destroy')
			->middleware('role_or_permission:admin|delete work orders');

		Route::post('sales/{sale}/work-orders/{workOrder}/restore', [\App\Http\Controllers\Pages\WorkOrderController::class, 'restore'])
			->withTrashed()
			->name('sales.work-orders.restore')
			->middleware('role:admin');

		Route::delete('sales/{sale}/work-orders/{workOrder}/force', [\App\Http\Controllers\Pages\WorkOrderController::class, 'forceDestroy'])
			->withTrashed()
			->name('sales.work-orders.force-destroy')
			->middleware('role:admin');

		Route::get('sales/{sale}/work-orders/{workOrder}/pdf', [\App\Http\Controllers\Pages\WorkOrderController::class, 'previewPdf'])
			->name('sales.work-orders.pdf')
			->middleware('role_or_permission:admin|view work orders');

		Route::post('sales/{sale}/work-orders/{workOrder}/send-email', [\App\Http\Controllers\Pages\WorkOrderController::class, 'sendEmail'])
			->name('sales.work-orders.send-email')
			->middleware('role_or_permission:admin|edit work orders');

		Route::post('sales/{sale}/work-orders/{workOrder}/stage-pick-ticket', [\App\Http\Controllers\Pages\WorkOrderController::class, 'stagePickTicket'])
			->name('sales.work-orders.stage-pick-ticket')
			->middleware('role_or_permission:admin|edit work orders');

		Route::post('sales/{sale}/stage-pick-ticket', [\App\Http\Controllers\Pages\SaleController::class, 'stagePickTicket'])
			->name('sales.stage-pick-ticket')
			->middleware('role_or_permission:admin|edit work orders');

		// Change Orders
		Route::get('sales/{sale}/change-orders/create', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'create'])
			->name('sales.change-orders.create')
			->middleware('role_or_permission:admin|edit estimates');

		Route::post('sales/{sale}/change-orders', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'store'])
			->name('sales.change-orders.store')
			->middleware('role_or_permission:admin|edit estimates');

		Route::get('sales/{sale}/change-orders/{changeOrder}', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'show'])
			->name('sales.change-orders.show')
			->middleware('role_or_permission:admin|edit estimates');

		Route::get('sales/{sale}/change-orders/{changeOrder}/pdf', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'previewPdf'])
			->name('sales.change-orders.pdf')
			->middleware('role_or_permission:admin|edit estimates');

		Route::post('sales/{sale}/change-orders/{changeOrder}/approve', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'approve'])
			->name('sales.change-orders.approve')
			->middleware('role_or_permission:admin|edit estimates');

		Route::post('sales/{sale}/change-orders/{changeOrder}/cancel', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'cancel'])
			->name('sales.change-orders.cancel')
			->middleware('role_or_permission:admin|edit estimates');

		Route::post('sales/{sale}/change-orders/{changeOrder}/send-email', [\App\Http\Controllers\Pages\ChangeOrderController::class, 'sendEmail'])
			->name('sales.change-orders.send-email')
			->middleware('role_or_permission:admin|edit estimates');

		// Accounts Receivable
		Route::get('ar', [\App\Http\Controllers\Pages\ArController::class, 'index'])
			->name('ar.index')
			->middleware('role_or_permission:admin|view invoices');

		Route::get('ar/aging', [\App\Http\Controllers\Pages\ArController::class, 'aging'])
			->name('ar.aging')
			->middleware('role_or_permission:admin|view invoices');

		// Invoices
		Route::get('sales/{sale}/invoices/create', [\App\Http\Controllers\Pages\InvoiceController::class, 'create'])
			->name('sales.invoices.create')
			->middleware('role_or_permission:admin|create invoices');

		Route::post('sales/{sale}/invoices', [\App\Http\Controllers\Pages\InvoiceController::class, 'store'])
			->name('sales.invoices.store')
			->middleware('role_or_permission:admin|create invoices');

		Route::get('sales/{sale}/invoices/{invoice}', [\App\Http\Controllers\Pages\InvoiceController::class, 'show'])
			->name('sales.invoices.show')
			->middleware('role_or_permission:admin|view invoices');

		Route::get('sales/{sale}/invoices/{invoice}/edit', [\App\Http\Controllers\Pages\InvoiceController::class, 'edit'])
			->name('sales.invoices.edit')
			->middleware('role_or_permission:admin|edit invoices');

		Route::put('sales/{sale}/invoices/{invoice}', [\App\Http\Controllers\Pages\InvoiceController::class, 'update'])
			->name('sales.invoices.update')
			->middleware('role_or_permission:admin|edit invoices');

		Route::post('sales/{sale}/invoices/{invoice}/void', [\App\Http\Controllers\Pages\InvoiceController::class, 'void'])
			->name('sales.invoices.void')
			->middleware('role_or_permission:admin|edit invoices');

		Route::get('sales/{sale}/invoices/{invoice}/pdf', [\App\Http\Controllers\Pages\InvoiceController::class, 'pdf'])
			->name('sales.invoices.pdf')
			->middleware('role_or_permission:admin|view invoices');

		Route::post('sales/{sale}/invoices/{invoice}/send-email', [\App\Http\Controllers\Pages\InvoiceController::class, 'sendEmail'])
			->name('sales.invoices.send-email')
			->middleware('role_or_permission:admin|edit invoices');

		// Invoice Payments
		Route::post('sales/{sale}/invoices/{invoice}/payments', [\App\Http\Controllers\Pages\InvoiceController::class, 'storePayment'])
			->name('sales.invoices.payments.store')
			->middleware('role_or_permission:admin|edit invoices');

		Route::delete('sales/{sale}/invoices/{invoice}/payments/{payment}', [\App\Http\Controllers\Pages\InvoiceController::class, 'destroyPayment'])
			->name('sales.invoices.payments.destroy')
			->middleware('role_or_permission:admin|edit invoices');

		Route::post('sales/{sale}/invoices/{invoice}/push-to-qbo', [\App\Http\Controllers\Pages\InvoiceController::class, 'pushToQbo'])
			->name('sales.invoices.push-to-qbo')
			->middleware('role_or_permission:admin|edit invoices');

		// Sale Deposits
		Route::post('sales/{sale}/deposits', [\App\Http\Controllers\Pages\SaleController::class, 'storeDeposit'])
			->name('sales.deposits.store')
			->middleware('role_or_permission:admin|edit estimates');

		Route::delete('sales/{sale}/deposits/{deposit}', [\App\Http\Controllers\Pages\SaleController::class, 'destroyDeposit'])
			->name('sales.deposits.destroy')
			->middleware('role_or_permission:admin|edit estimates');

		// Inventory Records
		Route::get('inventory', [\App\Http\Controllers\Pages\InventoryController::class, 'index'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('inventory.index');

		// RFC — Return From Customer
		Route::prefix('inventory/rfc')->name('inventory.rfc.')->middleware('role_or_permission:admin|view rfcs')->group(function () {
			Route::get('/', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'index'])->name('index');
			Route::get('/create', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'create'])->name('create');
			Route::post('/', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'store'])->name('store');
			Route::get('/{rfc}', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'show'])->name('show');
			Route::get('/{rfc}/edit', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'edit'])
				->middleware('role_or_permission:admin|create rfcs')->name('edit');
			Route::put('/{rfc}', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'update'])
				->middleware('role_or_permission:admin|create rfcs')->name('update');
			Route::post('/{rfc}/receive', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'receive'])
				->middleware('role_or_permission:admin|create rfcs')->name('receive');
			Route::delete('/{rfc}', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'destroy'])
				->middleware('role_or_permission:admin|create rfcs')->name('destroy');

			Route::post('/{rfc}/restore', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'restore'])
				->withTrashed()
				->middleware('role:admin')->name('restore');

			Route::delete('/{rfc}/force', [\App\Http\Controllers\Pages\CustomerReturnController::class, 'forceDestroy'])
				->withTrashed()
				->middleware('role:admin')->name('force-destroy');
		});

		// RTV — Return to Vendor
		Route::prefix('inventory/rtv')->name('inventory.rtv.')->middleware('role_or_permission:admin|view rtvs')->group(function () {
			Route::get('/', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'index'])->name('index');
			Route::get('/create', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'create'])->name('create');
			Route::post('/', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'store'])->name('store');
			Route::get('/{rtv}', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'show'])->name('show');
			Route::get('/{rtv}/edit', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'edit'])
				->middleware('role_or_permission:admin|edit rtvs')->name('edit');
			Route::put('/{rtv}', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'update'])
				->middleware('role_or_permission:admin|edit rtvs')->name('update');
			Route::post('/{rtv}/ship', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'ship'])
				->middleware('role_or_permission:admin|create rtvs')->name('ship');
			Route::post('/{rtv}/resolve', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'resolve'])
				->middleware('role_or_permission:admin|create rtvs')->name('resolve');
			Route::delete('/{rtv}', [\App\Http\Controllers\Pages\ReturnToVendorController::class, 'destroy'])
				->middleware('role_or_permission:admin|create rtvs')->name('destroy');
		});

		// Inventory Record show (wildcard — must come AFTER rfc/rtv groups)
		Route::get('inventory/{inventoryReceipt}', [\App\Http\Controllers\Pages\InventoryController::class, 'show'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('inventory.show');

		// Warehouse — Pick Tickets + Receive Inventory
		// Packing Lists
		Route::post('warehouse/pick-tickets/{pickTicket}/packing-lists', [\App\Http\Controllers\Pages\PackingListController::class, 'store'])
			->middleware('role_or_permission:admin|view pick tickets')
			->name('warehouse.packing-lists.store');

		Route::get('warehouse/packing-lists/{packingList}', [\App\Http\Controllers\Pages\PackingListController::class, 'show'])
			->middleware('role_or_permission:admin|view pick tickets')
			->name('warehouse.packing-lists.show');

		Route::patch('warehouse/packing-lists/{packingList}', [\App\Http\Controllers\Pages\PackingListController::class, 'update'])
			->middleware('role_or_permission:admin|view pick tickets')
			->name('warehouse.packing-lists.update');

		Route::get('warehouse/packing-lists/{packingList}/pdf', [\App\Http\Controllers\Pages\PackingListController::class, 'pdf'])
			->middleware('role_or_permission:admin|view pick tickets')
			->name('warehouse.packing-lists.pdf');

		Route::prefix('warehouse')->name('warehouse.')->group(function () {
			Route::get('receive', [\App\Http\Controllers\Pages\WarehouseReceiveController::class, 'index'])
				->middleware('role_or_permission:admin|view purchase orders')
				->name('receive');

			Route::get('pickups', [\App\Http\Controllers\Pages\WarehousePickupsController::class, 'index'])
				->middleware('role_or_permission:admin|view purchase orders')
				->name('pickups.index');

			Route::get('pickups/{purchaseOrder}', [\App\Http\Controllers\Pages\WarehousePickupsController::class, 'show'])
				->middleware('role_or_permission:admin|view purchase orders')
				->name('pickups.show');

			Route::get('pick-tickets', [\App\Http\Controllers\Pages\WarehousePickTicketController::class, 'index'])
				->middleware('role_or_permission:admin|view pick tickets')
				->name('pick-tickets.index');

			Route::get('pick-tickets/{pickTicket}', [\App\Http\Controllers\Pages\WarehousePickTicketController::class, 'show'])
				->middleware('role_or_permission:admin|view pick tickets')
				->name('pick-tickets.show');

			Route::get('pick-tickets/{pickTicket}/pdf', [\App\Http\Controllers\Pages\WarehousePickTicketController::class, 'pdf'])
				->middleware('role_or_permission:admin|view pick tickets')
				->name('pick-tickets.pdf');

			Route::post('pick-tickets/{pickTicket}/unstage', [\App\Http\Controllers\Pages\WarehousePickTicketController::class, 'unstage'])
				->middleware('role_or_permission:admin|view pick tickets')
				->name('pick-tickets.unstage');

			Route::patch('pick-tickets/{pickTicket}/status', [\App\Http\Controllers\Pages\WarehousePickTicketController::class, 'updateStatus'])
				->middleware('role_or_permission:admin|view pick tickets')
				->name('pick-tickets.update-status');
		});

		// Purchase Orders — index
		Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.index');

		// RFMs — index
		Route::get('rfms', [\App\Http\Controllers\Pages\RfmController::class, 'index'])
			->middleware('role_or_permission:admin|view rfms')
			->name('rfms.index');

		// Work Orders — index
		Route::get('work-orders', [\App\Http\Controllers\Pages\WorkOrderController::class, 'index'])
			->middleware('role_or_permission:admin|view work orders')
			->name('work-orders.index');

		// Purchase Orders — create/store scoped to a sale
		Route::get('sales/{sale}/purchase-orders/create', [PurchaseOrderController::class, 'create'])
			->middleware('role_or_permission:admin|create purchase orders')
			->name('sales.purchase-orders.create');

		Route::post('sales/{sale}/purchase-orders', [PurchaseOrderController::class, 'store'])
			->middleware('role_or_permission:admin|create purchase orders')
			->name('sales.purchase-orders.store');

		// Purchase Orders — stock (no sale) create/store — must be before {purchaseOrder} wildcard
		Route::get('purchase-orders/create', [PurchaseOrderController::class, 'createStock'])
			->middleware('role_or_permission:admin|create purchase orders')
			->name('purchase-orders.create-stock');

		Route::post('purchase-orders', [PurchaseOrderController::class, 'storeStock'])
			->middleware('role_or_permission:admin|create purchase orders')
			->name('purchase-orders.store-stock');

		Route::get('purchase-orders/catalog-search', [PurchaseOrderController::class, 'catalogSearch'])
			->middleware('role_or_permission:admin|create purchase orders|edit purchase orders')
			->name('purchase-orders.catalog-search');

		Route::get('purchase-orders/jump', [PurchaseOrderController::class, 'jump'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.jump');

		// Purchase Orders — standalone (view/edit/pdf/send)
		Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.show');

		Route::get('purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.edit');

		Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.update');

		Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'previewPdf'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.pdf');

		Route::get('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveForm'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.receive.form');

		Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.receive');

		Route::get('purchase-orders/{purchaseOrder}/tags', [\App\Http\Controllers\Pages\InventoryTagController::class, 'poTags'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.tags');

		Route::get('inventory/receipts/{receipt}/tag', [\App\Http\Controllers\Pages\InventoryTagController::class, 'tag'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('inventory.receipts.tag');

		Route::post('purchase-orders/{purchaseOrder}/send-email', [PurchaseOrderController::class, 'sendEmail'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.send-email');

		// Purchase Order Documents
		Route::post('purchase-orders/{purchaseOrder}/documents', [\App\Http\Controllers\Pages\PurchaseOrderDocumentController::class, 'store'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.documents.store');

		Route::delete('purchase-orders/{purchaseOrder}/documents/{document}', [\App\Http\Controllers\Pages\PurchaseOrderDocumentController::class, 'destroy'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.documents.destroy');

		Route::get('purchase-orders/{purchaseOrder}/documents/{document}/download', [\App\Http\Controllers\Pages\PurchaseOrderDocumentController::class, 'download'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('purchase-orders.documents.download');

		Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
			->middleware('role_or_permission:admin|delete purchase orders')
			->name('purchase-orders.destroy');

		Route::delete('purchase-orders/{purchaseOrder}/force', [PurchaseOrderController::class, 'forceDestroy'])
			->withTrashed()
			->middleware('role:admin')
			->name('purchase-orders.force-destroy');

		Route::post('purchase-orders/{purchaseOrder}/restore', [PurchaseOrderController::class, 'restore'])
			->withTrashed()
			->middleware('role:admin')
			->name('purchase-orders.restore');

        Route::post('job-sites', [JobSiteCustomerController::class, 'store'])
            ->name('job-sites.store');

        Route::patch('job-sites/{customer}', [JobSiteCustomerController::class, 'update'])
            ->name('job-sites.update');

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

    // Expect comma-separated Microsoft calendar IDs, e.g. ?calendar_ids=24,25
    $calendarIds = collect(explode(',', (string) $request->query('calendar_ids')))
        ->map(fn ($v) => trim($v))
        ->filter()
        ->values();

    $eventsQuery = \App\Models\CalendarEvent::query()
        ->where('owner_user_id', auth()->id())
        ->whereNull('deleted_at');

    // If calendar_ids provided, filter by external_event_links.external_calendar_id (Graph calendar id)
    if ($calendarIds->count() > 0) {
        $eventsQuery->whereIn('id', function ($sub) use ($calendarIds) {
            $sub->select('calendar_event_id')
                ->from('external_event_links')
                ->whereIn('external_calendar_id', $calendarIds->all());
        });
    }

    // Load events once
    $eventModels = $eventsQuery->get();

    // Link: calendar_event_id -> external_calendar_id (Graph calendar id)
    $linksByEventId = \App\Models\ExternalEventLink::query()
        ->whereIn('calendar_event_id', $eventModels->pluck('id'))
        ->get()
        ->keyBy('calendar_event_id');

    // Map: Graph calendar id -> MicrosoftCalendar (DB id used by dropdown)
    $microsoftCalendars = \App\Models\MicrosoftCalendar::query()
        ->where('microsoft_account_id', optional(auth()->user()->microsoftAccount)->id)
        ->get();

    $calendarByGraphId = $microsoftCalendars->keyBy('calendar_id')->filter();

    // Normalized group_id -> min DB id (matches FM_CALENDAR_OPTIONS in the dropdown)
    $normalizedIdByGroupId = \App\Models\MicrosoftCalendar::whereNotNull('group_id')
        ->selectRaw('MIN(id) as id, group_id')
        ->groupBy('group_id')
        ->pluck('id', 'group_id');

    $events = $eventModels->map(function ($e) use ($linksByEventId, $calendarByGraphId, $normalizedIdByGroupId) {

        $isAllDay = $e->starts_at
            && $e->ends_at
            && $e->starts_at->format('H:i:s') === '00:00:00'
            && $e->ends_at->format('H:i:s') === '00:00:00'
            && $e->ends_at->greaterThan($e->starts_at);

        $graphCalendarId = optional($linksByEventId->get($e->id))->external_calendar_id;
        $mc = $graphCalendarId ? $calendarByGraphId->get($graphCalendarId) : null;

        return [
            'id'    => (string) $e->id,
            'title' => (string) $e->title,

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

                // provider: never null
                'provider'    => $e->provider ?: ($graphCalendarId ? 'microsoft' : 'local'),

                // for dropdown auto-select + future move logic
                // Use normalized id (min per group_id) so it matches FM_CALENDAR_OPTIONS
                'calendar_id'          => $mc && $mc->group_id
                    ? ($normalizedIdByGroupId[$mc->group_id] ?? $mc->id)
                    : optional($mc)->id,
                'provider_calendar_id' => $graphCalendarId,      // Graph calendar id
                'calendar_name'        => optional($mc)->name,   // optional UI

                // helper fields for all-day UI
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
		
Route::post('calendar/events/{event}/move', [CalendarEventController::class, 'move'])
    ->name('calendar.events.move');
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

            Route::get('documents/{document}/reprint', [OpportunityDocumentController::class, 'reprint'])
                ->name('opportunities.documents.reprint');

            // Generated document — new editable flow
            Route::get('documents/create/{template}', [OpportunityDocumentController::class, 'createGenerated'])
                ->name('opportunities.documents.create-generated');

            Route::post('documents/generated', [OpportunityDocumentController::class, 'storeGenerated'])
                ->name('opportunities.documents.store-generated');

            Route::get('documents/{document}/view', [OpportunityDocumentController::class, 'showGenerated'])
                ->name('opportunities.documents.show-generated');

            Route::get('documents/{document}/edit-fields', [OpportunityDocumentController::class, 'editGenerated'])
                ->name('opportunities.documents.edit-generated');

            Route::put('documents/{document}/generated', [OpportunityDocumentController::class, 'updateGenerated'])
                ->name('opportunities.documents.update-generated');

            Route::get('documents/{document}/pdf', [OpportunityDocumentController::class, 'downloadPdf'])
                ->name('opportunities.documents.pdf');

            // Flooring Sign-Off routes
            Route::get('sign-offs/create', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'create'])
                ->name('opportunities.sign-offs.create');
            Route::post('sign-offs', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'store'])
                ->name('opportunities.sign-offs.store');
            Route::get('sign-offs/{signOff}', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'show'])
                ->name('opportunities.sign-offs.show');
            Route::put('sign-offs/{signOff}', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'update'])
                ->name('opportunities.sign-offs.update');
            Route::get('sign-offs/{signOff}/pdf', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'pdf'])
                ->name('opportunities.sign-offs.pdf');
            Route::delete('sign-offs/{signOff}', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'destroy'])
                ->name('opportunities.sign-offs.destroy');
            Route::delete('sign-offs/{signOff}/force', [\App\Http\Controllers\Pages\FlooringSignOffController::class, 'forceDestroy'])
                ->name('opportunities.sign-offs.forceDestroy');

            // RFM routes
            Route::get('rfms/create', [RfmController::class, 'create'])
                ->middleware('role_or_permission:admin|create rfms')
                ->name('opportunities.rfms.create');

            Route::post('rfms', [RfmController::class, 'store'])
                ->middleware('role_or_permission:admin|create rfms')
                ->name('opportunities.rfms.store');

            Route::get('rfms/{rfm}', [RfmController::class, 'show'])
                ->middleware('role_or_permission:admin|view rfms')
                ->name('opportunities.rfms.show');

            Route::get('rfms/{rfm}/pdf', [RfmController::class, 'pdf'])
                ->middleware('role_or_permission:admin|view rfms')
                ->name('opportunities.rfms.pdf');

            Route::get('rfms/{rfm}/edit', [RfmController::class, 'edit'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.edit');

            Route::patch('rfms/{rfm}', [RfmController::class, 'update'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.update');

            Route::patch('rfms/{rfm}/status', [RfmController::class, 'updateStatus'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.updateStatus');

            Route::delete('rfms/{rfm}', [RfmController::class, 'destroy'])
                ->middleware('role_or_permission:admin|delete rfms')
                ->name('opportunities.rfms.destroy');

            Route::delete('rfms/{rfm}/force', [RfmController::class, 'forceDestroy'])
                ->withTrashed()
                ->middleware('role:admin')
                ->name('opportunities.rfms.force-destroy');
        });

    // ── Samples ────────────────────────────────────────────────────────────────
    // Static routes MUST come before wildcard {sample} routes
    Route::get('samples', [\App\Http\Controllers\Pages\SampleController::class, 'index'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.index');

    Route::get('samples/styles/search', [\App\Http\Controllers\Pages\SampleController::class, 'searchStyles'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.styles.search');

    Route::get('samples/create', [\App\Http\Controllers\Pages\SampleController::class, 'create'])
        ->middleware('role_or_permission:admin|create samples')
        ->name('samples.create');

    Route::post('samples', [\App\Http\Controllers\Pages\SampleController::class, 'store'])
        ->middleware('role_or_permission:admin|create samples')
        ->name('samples.store');

    // Wildcard {sample} routes after all static routes
    Route::get('samples/{sample}', [\App\Http\Controllers\Pages\SampleController::class, 'show'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.show');

    Route::get('samples/{sample}/label', [\App\Http\Controllers\Pages\SampleController::class, 'label'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.label');

    Route::get('samples/{sample}/edit', [\App\Http\Controllers\Pages\SampleController::class, 'edit'])
        ->middleware('role_or_permission:admin|edit samples')
        ->name('samples.edit');

    Route::put('samples/{sample}', [\App\Http\Controllers\Pages\SampleController::class, 'update'])
        ->middleware('role_or_permission:admin|edit samples')
        ->name('samples.update');

    Route::delete('samples/{sample}', [\App\Http\Controllers\Pages\SampleController::class, 'destroy'])
        ->middleware('role_or_permission:admin|delete samples')
        ->name('samples.destroy');

    Route::post('samples/{sample}/checkouts/{checkout}/return', [\App\Http\Controllers\Pages\SampleController::class, 'returnCheckout'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('samples.checkouts.return');

    // ── Sample Sets — static routes FIRST, wildcards after ────────────────────
    Route::get('sample-sets/create', [\App\Http\Controllers\Pages\SampleSetController::class, 'create'])
        ->middleware('role_or_permission:admin|create samples')
        ->name('sample-sets.create');

    Route::get('sample-sets/styles-by-line/{productLine}', [\App\Http\Controllers\Pages\SampleSetController::class, 'stylesByLine'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('sample-sets.styles-by-line');

    Route::post('sample-sets', [\App\Http\Controllers\Pages\SampleSetController::class, 'store'])
        ->middleware('role_or_permission:admin|create samples')
        ->name('sample-sets.store');

    Route::get('sample-sets/{sampleSet}', [\App\Http\Controllers\Pages\SampleSetController::class, 'show'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('sample-sets.show');

    Route::get('sample-sets/{sampleSet}/label', [\App\Http\Controllers\Pages\SampleSetController::class, 'label'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('sample-sets.label');

    Route::get('sample-sets/{sampleSet}/edit', [\App\Http\Controllers\Pages\SampleSetController::class, 'edit'])
        ->middleware('role_or_permission:admin|edit samples')
        ->name('sample-sets.edit');

    Route::put('sample-sets/{sampleSet}', [\App\Http\Controllers\Pages\SampleSetController::class, 'update'])
        ->middleware('role_or_permission:admin|edit samples')
        ->name('sample-sets.update');

    Route::delete('sample-sets/{sampleSet}', [\App\Http\Controllers\Pages\SampleSetController::class, 'destroy'])
        ->middleware('role_or_permission:admin|delete samples')
        ->name('sample-sets.destroy');

    Route::post('sample-sets/{sampleSet}/checkout', [\App\Http\Controllers\Pages\SampleSetController::class, 'checkout'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('sample-sets.checkout');

    Route::post('sample-sets/{sampleSet}/checkouts/{checkout}/return', [\App\Http\Controllers\Pages\SampleSetController::class, 'returnCheckout'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('sample-sets.checkouts.return');

    }); // end pages group


// Profile routes (authenticated)
Route::middleware('auth')->group(function () {

    // User Calendar Preferences API
    Route::get('/api/user/calendar-preferences', [UserCalendarPreferenceController::class, 'show'])
        ->name('api.user.calendar_preferences.show');

    Route::post('/api/user/calendar-preferences', [UserCalendarPreferenceController::class, 'upsert'])
        ->name('api.user.calendar_preferences.upsert');

    // Existing profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email templates (per-user)
    Route::get('/settings/email-templates', [\App\Http\Controllers\Pages\EmailTemplateController::class, 'index'])
        ->name('pages.settings.email-templates.index');
    Route::post('/settings/email-templates/{type}', [\App\Http\Controllers\Pages\EmailTemplateController::class, 'save'])
        ->name('pages.settings.email-templates.save');
    Route::delete('/settings/email-templates/{type}', [\App\Http\Controllers\Pages\EmailTemplateController::class, 'reset'])
        ->name('pages.settings.email-templates.reset');
	
	//estimates
	Route::get('/api/product-pricing', [\App\Http\Controllers\Api\ProductPricingController::class, 'show'])
    ->middleware([
        'permission:view estimates',
        'permission:create estimates',
        'permission:edit estimates',
    ])
    ->name('api.product-pricing');

});

// ─── Mobile auth (unauthenticated) ────────────────────────────────────────────
Route::prefix('m')->name('mobile.')->group(function () {
    Route::get('login', [\App\Http\Controllers\Mobile\MobileSessionController::class, 'create'])
        ->middleware('guest:mobile')
        ->name('login');
    Route::post('login', [\App\Http\Controllers\Mobile\MobileSessionController::class, 'store'])
        ->middleware('guest:mobile')
        ->name('login.store');
    Route::post('logout', [\App\Http\Controllers\Mobile\MobileSessionController::class, 'destroy'])
        ->name('logout');
});

// ─── Mobile views ─────────────────────────────────────────────────────────────
Route::middleware(['auth.mobile'])->prefix('m')->name('mobile.')->group(function () {
    Route::get('/', fn () => view('mobile.home'))->name('home');

    Route::get('po/{purchaseOrder}', [\App\Http\Controllers\Mobile\PurchaseOrderController::class, 'show'])
        ->middleware('role_or_permission:admin|view purchase orders')
        ->name('purchase-orders.show');

    Route::get('po/{purchaseOrder}/receive', [\App\Http\Controllers\Mobile\PurchaseOrderController::class, 'receiveForm'])
        ->middleware('role_or_permission:admin|edit purchase orders')
        ->name('purchase-orders.receive');

    Route::get('inventory', [\App\Http\Controllers\Mobile\InventoryController::class, 'index'])
        ->middleware('role_or_permission:admin|view inventory')
        ->name('inventory.index');

    Route::get('inventory/{receipt}', [\App\Http\Controllers\Mobile\InventoryController::class, 'show'])
        ->middleware('role_or_permission:admin|view purchase orders')
        ->name('inventory.show');

    // Warehouse hub + pick tickets
    Route::middleware('role_or_permission:admin|view inventory')->group(function () {
        Route::get('warehouse', [\App\Http\Controllers\Mobile\WarehouseController::class, 'index'])
            ->name('warehouse.index');

        Route::get('warehouse/pick-tickets', [\App\Http\Controllers\Mobile\WarehouseController::class, 'pickTickets'])
            ->middleware('role_or_permission:admin|view pick tickets')
            ->name('warehouse.pick-tickets.index');

        Route::get('warehouse/pt/{pickTicket}', [\App\Http\Controllers\Mobile\WarehouseController::class, 'showPickTicket'])
            ->middleware('role_or_permission:admin|view pick tickets')
            ->name('warehouse.pick-tickets.show');

        Route::patch('warehouse/pt/{pickTicket}/status', [\App\Http\Controllers\Mobile\WarehouseController::class, 'updatePickTicketStatus'])
            ->middleware('role_or_permission:admin|view pick tickets')
            ->name('warehouse.pick-tickets.update-status');

        Route::get('warehouse/rfc', [\App\Http\Controllers\Mobile\WarehouseController::class, 'rfcs'])
            ->name('warehouse.rfc.index');

        Route::get('warehouse/rfc/{customerReturn}', [\App\Http\Controllers\Mobile\WarehouseController::class, 'showRfc'])
            ->name('warehouse.rfc.show');

        Route::get('warehouse/rtv', [\App\Http\Controllers\Mobile\WarehouseController::class, 'rtvs'])
            ->name('warehouse.rtv.index');

        Route::get('warehouse/rtv/{inventoryReturn}', [\App\Http\Controllers\Mobile\WarehouseController::class, 'showRtv'])
            ->name('warehouse.rtv.show');
    });

    Route::get('work-orders', [\App\Http\Controllers\Mobile\WorkOrderController::class, 'index'])
        ->middleware('role_or_permission:admin|view work orders')
        ->name('work-orders.index');

    Route::get('wo/{workOrder}', [\App\Http\Controllers\Mobile\WorkOrderController::class, 'show'])
        ->middleware('role_or_permission:admin|view work orders')
        ->name('work-orders.show');

    Route::post('wo/{workOrder}/photos', [\App\Http\Controllers\Mobile\WorkOrderController::class, 'uploadPhotos'])
        ->middleware('role_or_permission:admin|view work orders')
        ->name('work-orders.upload-photos');

    Route::get('opportunities', [\App\Http\Controllers\Mobile\OpportunityController::class, 'index'])
        ->name('opportunities.index');

    Route::get('opportunity/{opportunity}', [\App\Http\Controllers\Mobile\OpportunityController::class, 'show'])
        ->name('opportunity.show');

    Route::get('opportunity/{opportunity}/photos', [\App\Http\Controllers\Mobile\PhotoGalleryController::class, 'show'])
        ->name('opportunity.photos');

    Route::post('opportunity/{opportunity}/photos', [\App\Http\Controllers\Mobile\PhotoGalleryController::class, 'uploadPhotos'])
        ->name('opportunity.photos.upload');

    Route::get('rfms', [\App\Http\Controllers\Mobile\RfmController::class, 'index'])
        ->middleware('role_or_permission:admin|view rfms')
        ->name('rfms.index');

    Route::get('rfm/{rfm}', [\App\Http\Controllers\Mobile\RfmController::class, 'show'])
        ->middleware('role_or_permission:admin|view rfms')
        ->name('rfms.show');

    Route::post('rfm/{rfm}/photos', [\App\Http\Controllers\Mobile\RfmController::class, 'uploadPhotos'])
        ->middleware('role_or_permission:admin|view rfms')
        ->name('rfms.upload-photos');

    Route::get('samples', [\App\Http\Controllers\Mobile\SampleController::class, 'index'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.index');

    Route::get('sample/{sampleId}', [\App\Http\Controllers\Mobile\SampleController::class, 'show'])
        ->middleware('role_or_permission:admin|view samples')
        ->name('samples.show');

    Route::get('sample/{sampleId}/checkout', [\App\Http\Controllers\Mobile\SampleController::class, 'checkout'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('samples.checkout');

    Route::post('sample/{sampleId}/checkout', [\App\Http\Controllers\Mobile\SampleController::class, 'storeCheckout'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('samples.checkout.store');

    Route::get('sample/{setId}/checkout-set', [\App\Http\Controllers\Mobile\SampleController::class, 'checkoutSet'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('sample-sets.checkout');

    Route::post('sample/{setId}/checkout-set', [\App\Http\Controllers\Mobile\SampleController::class, 'storeCheckoutSet'])
        ->middleware('role_or_permission:admin|manage sample checkouts')
        ->name('sample-sets.checkout.store');
});

// ─── Installer portal ──────────────────────────────────────────────────────────
Route::middleware(['auth.mobile', 'role:installer'])->prefix('installer')->name('installer.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Installer\DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('wo/{workOrder}/status', [\App\Http\Controllers\Installer\WorkOrderController::class, 'updateStatus'])
        ->name('wo.update-status');
});

require __DIR__ . '/auth.php';
