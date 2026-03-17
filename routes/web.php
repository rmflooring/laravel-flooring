<?php

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
    return view('welcome');
});

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

        Route::post('product-lines/{product_line}/product-styles/{style}/duplicate', [ProductStyleController::class, 'duplicate'])
            ->middleware('role_or_permission:admin|view product styles')
            ->name('product_styles.duplicate');
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
			return view('admin.estimates.create', [
				'opportunity'       => $opportunity,
				'employees'         => $employees,
				'defaultTaxGroupId' => $defaultTaxGroupId,
				'taxGroups'         => $taxGroups,
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
			->middleware('permission:create estimates')
			->name('sales.destroy');

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

		Route::get('sales/{sale}/work-orders/{workOrder}/pdf', [\App\Http\Controllers\Pages\WorkOrderController::class, 'previewPdf'])
			->name('sales.work-orders.pdf')
			->middleware('role_or_permission:admin|view work orders');

		Route::post('sales/{sale}/work-orders/{workOrder}/send-email', [\App\Http\Controllers\Pages\WorkOrderController::class, 'sendEmail'])
			->name('sales.work-orders.send-email')
			->middleware('role_or_permission:admin|edit work orders');

		Route::post('sales/{sale}/work-orders/{workOrder}/stage-pick-ticket', [\App\Http\Controllers\Pages\WorkOrderController::class, 'stagePickTicket'])
			->name('sales.work-orders.stage-pick-ticket')
			->middleware('role_or_permission:admin|edit work orders');

		// Inventory
		Route::get('inventory', [\App\Http\Controllers\Pages\InventoryController::class, 'index'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('inventory.index');

		Route::get('inventory/{inventoryReceipt}', [\App\Http\Controllers\Pages\InventoryController::class, 'show'])
			->middleware('role_or_permission:admin|view purchase orders')
			->name('inventory.show');

		// Warehouse — Pick Tickets
		Route::prefix('warehouse')->name('warehouse.')->group(function () {
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

		Route::post('purchase-orders/{purchaseOrder}/send-email', [PurchaseOrderController::class, 'sendEmail'])
			->middleware('role_or_permission:admin|edit purchase orders')
			->name('purchase-orders.send-email');

		Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
			->middleware('role_or_permission:admin|delete purchase orders')
			->name('purchase-orders.destroy');

		Route::delete('purchase-orders/{purchaseOrder}/force', [PurchaseOrderController::class, 'forceDestroy'])
			->withTrashed()
			->middleware('role:admin')
			->name('purchase-orders.force-destroy');

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

    $events = $eventModels->map(function ($e) use ($linksByEventId, $calendarByGraphId) {

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
                'calendar_id'          => optional($mc)->id,     // matches <option value="...">
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

            Route::get('rfms/{rfm}/edit', [RfmController::class, 'edit'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.edit');

            Route::patch('rfms/{rfm}', [RfmController::class, 'update'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.update');

            Route::patch('rfms/{rfm}/status', [RfmController::class, 'updateStatus'])
                ->middleware('role_or_permission:admin|edit rfms')
                ->name('opportunities.rfms.updateStatus');
        });

    });

	


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

require __DIR__ . '/auth.php';
