<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalog\InsuranceCompanyController;
use App\Http\Controllers\Catalog\MedicationController;
use App\Http\Controllers\Catalog\ServiceController;
use App\Http\Controllers\Catalog\UnitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceCategoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Patients — data_entry, admin, super_admin
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin|admin|data_entry')
        ->group(function () {
            Route::resource('patients', PatientController::class)->except(['show']);
        });

    /*
    |----------------------------------------------------------------------
    | Admissions — admin, super_admin (manage); all auth users (view)
    |
    | IMPORTANT: fixed-path routes (/create, /edit) must be registered
    | before the wildcard show route so they are not swallowed by
    | admissions/{admission}.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::resource('admissions', AdmissionController::class)
            ->except(['show', 'destroy']);
        Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])
            ->name('admissions.discharge');
    });

    // show is open to every authenticated user — registered AFTER the resource
    // so that admissions/create and admissions/{id}/edit are matched first.
    Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');

    /*
    |----------------------------------------------------------------------
    | Invoices
    |  - index, show, print → all authenticated users (view_invoices)
    |  - add/remove items   → data_entry and above
    |  - finalize           → admin and above only
    |
    | Same fixed-path-before-wildcard rule applies: register
    | invoices/{invoice}/print BEFORE the open show wildcard.
    |----------------------------------------------------------------------
    */
    // Finalize — admin+ only
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])
            ->name('invoices.finalize');
    });

    // Add / remove items — data_entry and above
    Route::middleware('role:super_admin|admin|data_entry')->group(function () {
        Route::post('invoices/{invoice}/items', [InvoiceController::class, 'addItem'])
            ->name('invoices.items.store');
        Route::delete('invoices/{invoice}/items/{item}', [InvoiceController::class, 'removeItem'])
            ->name('invoices.items.destroy');
    });

    // Print PDF — all auth (open)
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])
        ->name('invoices.print');

    // Index & show — all auth, registered last so wildcards don't shadow fixed paths
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    /*
    |----------------------------------------------------------------------
    | Reports — admin and above only
    |  export must be registered before the wildcard index to avoid
    |  any future route shadowing issues.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin|admin')
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get('export',              [ReportController::class, 'export'])           ->name('export');
            Route::get('claim/print',         [ReportController::class, 'claimPrint'])        ->name('claim.print');
            Route::get('claim',               [ReportController::class, 'claim'])             ->name('claim');
            Route::get('patient-list/print',  [ReportController::class, 'patientListPrint'])  ->name('patient-list.print');
            Route::get('patient-list',        [ReportController::class, 'patientList'])       ->name('patient-list');
            Route::get('summary/print',       [ReportController::class, 'summaryPrint'])      ->name('summary.print');
            Route::get('summary',             [ReportController::class, 'summary'])            ->name('summary');
            Route::get('performance/print',   [ReportController::class, 'performancePrint'])  ->name('performance.print');
            Route::get('performance',         [ReportController::class, 'performance'])        ->name('performance');
            Route::get('/',                   [ReportController::class, 'index'])              ->name('index');
        });

    /*
    |----------------------------------------------------------------------
    | User Management — super_admin only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/',               [UserController::class, 'index'])        ->name('index');
            Route::get('/create',         [UserController::class, 'create'])       ->name('create');
            Route::post('/',              [UserController::class, 'store'])        ->name('store');
            Route::get('/{user}/edit',    [UserController::class, 'edit'])         ->name('edit');
            Route::put('/{user}',         [UserController::class, 'update'])       ->name('update');
            Route::post('/{user}/toggle', [UserController::class, 'toggleActive']) ->name('toggle-active');
            Route::delete('/{user}',      [UserController::class, 'destroy'])      ->name('destroy');
        });

    /*
    |----------------------------------------------------------------------
    | Catalog — super_admin and admin only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin|admin')
        ->prefix('catalog')
        ->name('catalog.')
        ->group(function () {
            Route::resource('medications', MedicationController::class)
                ->except(['show'])
                ->names('medications');

            Route::resource('services', ServiceController::class)
                ->except(['show'])
                ->names('services');

            Route::resource('insurance-companies', InsuranceCompanyController::class)
                ->except(['show'])
                ->names('insurance');

            Route::resource('invoice-categories', InvoiceCategoryController::class)
                ->except(['show'])
                ->names('invoice-categories');

            Route::resource('units', UnitController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('units');
        });

    /*
    |----------------------------------------------------------------------
    | Settings — super_admin only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')
        ->name('settings.')
        ->group(function () {
            Route::get('/settings',  [SettingsController::class, 'index'])  ->name('index');
            Route::put('/settings',  [SettingsController::class, 'update']) ->name('update');
        });
});
