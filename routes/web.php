<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyAdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web del Sistema
|--------------------------------------------------------------------------
*/

// Endpoint técnico de diagnóstico (público, sin auth, respuesta JSON)
Route::get('/health', HealthController::class)->name('health');

// Rutas de autenticación (invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Rutas protegidas (autenticadas)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Módulos exclusivos para super_admin
    Route::middleware('super_admin')->group(function () {
        Route::resource('companies', CompanyController::class);
        Route::resource('company-admins', CompanyAdminController::class)->parameters([
            'company-admins' => 'company_admin',
        ]);
    });

    // Módulos accesibles para company_admin (y super_admin)
    Route::middleware('company_admin')->group(function () {
        // Personal interno (Staff / Mecánicos)
        Route::patch('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        Route::resource('staff', StaffController::class)->except(['show', 'destroy']);

        // Clientes y Propietarios
        Route::resource('customers', CustomerController::class);

        // Flujo contextual de vehículos por cliente
        Route::get('customers/{customer}/vehicles/create', [VehicleController::class, 'createForCustomer'])->name('customers.vehicles.create');

        // Módulo general de Vehículos
        Route::resource('vehicles', VehicleController::class);
    });
});


