<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorRepController;
use App\Http\Controllers\Admin\ProjectManagerController;
use App\Http\Controllers\Admin\LabourTypeController;
use App\Http\Controllers\Admin\UnitMeasureController;
use App\Http\Controllers\Admin\CustomerTypeController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\DetailTypeController;
use App\Http\Controllers\Admin\TaxAgencyController;
use App\Http\Controllers\Admin\GLAccountController;

use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\LabourItemController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ProductLineController;
use App\Http\Controllers\Admin\ProductStyleController;
use App\Http\Controllers\Admin\EstimateController;

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


// TEMP: Estimate UI mock preview (no DB yet)
Route::get('/admin/estimates/mock-create', function () {
    return view('admin.estimates.mock-create');
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

            Route::resource('unit-measures', UnitMeasureController::class)->names('unit_measures');
            Route::resource('customer-types', CustomerTypeController::class)->names('customer_types');

            Route::prefix('ajax/gl-accounts')->group(function () {
                Route::get('detail-types', [GLAccountController::class, 'getDetailTypes'])->name('gl_accounts.detail_types');
                Route::get('parent-accounts', [GLAccountController::class, 'getParentAccounts'])->name('gl_accounts.parent_accounts');
            });

            // Estimates (admin-only for now)
			Route::get('/estimates/{estimate}/edit', [EstimateController::class, 'edit'])
				->name('estimates.edit');

			Route::post('/estimates', [EstimateController::class, 'store'])
				->name('estimates.store');

			Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])
				->name('estimates.update');

        });

        /*
        |----------------------------------------
        | Operational Management (permission-based)
        |----------------------------------------
        */

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



// Profile routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
