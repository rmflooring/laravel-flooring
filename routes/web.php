<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GLAccountController;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Test routes (remove these later if not needed)
Route::get('/hello', function () {
    return 'Hello from Laravel!';
});

Route::get('/test-view', function () {
    return view('welcome');
});

// Dashboard (authenticated)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin routes (protected)
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin-settings', function () {
        return view('admin.settings');
    })->name('admin.settings');

    // Resource routes
    Route::resource('users', UserController::class)->names('admin.users');
    Route::resource('roles', RoleController::class)->names('admin.roles');
    Route::resource('customers', CustomerController::class)->names('admin.customers');
    Route::resource('vendors', VendorController::class)->names('admin.vendors');
    Route::resource('vendor-reps', VendorRepController::class)->names('admin.vendor_reps');
    Route::resource('project-managers', ProjectManagerController::class)->names('admin.project_managers');
    Route::resource('labour-types', LabourTypeController::class)->names('admin.labour_types');
    Route::resource('unit-measures', UnitMeasureController::class)->names('admin.unit_measures');
    Route::resource('customer-types', CustomerTypeController::class)->names('admin.customer_types');
    Route::resource('account-types', AccountTypeController::class)->names('admin.account_types');
    Route::resource('detail-types', DetailTypeController::class)->names('admin.detail_types');
    Route::resource('tax-agencies', TaxAgencyController::class)->names('admin.tax_agencies');
    Route::resource('gl-accounts', GLAccountController::class)->names('admin.gl_accounts');

    // Ajax routes for dynamic dropdowns (inside admin group)
    Route::get('/gl-accounts/detail-types', [GLAccountController::class, 'getDetailTypes'])->name('gl_accounts.detail_types');
    Route::get('/gl-accounts/parent-accounts', [GLAccountController::class, 'getParentAccounts'])->name('gl_accounts.parent_accounts');
});

// Profile routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
