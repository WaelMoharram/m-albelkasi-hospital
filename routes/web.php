<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalog\InsuranceCompanyController;
use App\Http\Controllers\Catalog\MedicationController;
use App\Http\Controllers\Catalog\ServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
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
    |----------------------------------------------------------------------
    */
    Route::get('admissions/{admission}', [AdmissionController::class, 'show'])->name('admissions.show');

    Route::middleware('role:super_admin|admin')->group(function () {
        Route::resource('admissions', AdmissionController::class)
            ->except(['show', 'destroy']);
        Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'discharge'])
            ->name('admissions.discharge');
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
        });
});
