<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyAdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySubscriptionController;
use App\Http\Controllers\CompanySubscriptionStatusController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MaintenanceOrderController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubscriptionPlanController;
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

    // Estado de cuenta y suscripción (accesible siempre para ver estatus / pantalla de bloqueo)
    Route::get('subscription-status', [CompanySubscriptionStatusController::class, 'show'])->name('subscription.status');

    // Módulos exclusivos para super_admin (Planes, Empresas, Suscripciones)
    Route::middleware('super_admin')->group(function () {
        Route::resource('companies', CompanyController::class);
        Route::resource('company-admins', CompanyAdminController::class)->parameters([
            'company-admins' => 'company_admin',
        ]);

        // Gestión de Planes SaaS
        Route::patch('subscription-plans/{subscription_plan}/toggle-status', [SubscriptionPlanController::class, 'toggleStatus'])->name('subscription-plans.toggle-status');
        Route::resource('subscription-plans', SubscriptionPlanController::class)->except(['show', 'destroy']);

        // Gestión de Suscripciones por Empresa
        Route::post('company-subscriptions/{company_subscription}/renew', [CompanySubscriptionController::class, 'renew'])->name('company-subscriptions.renew');
        Route::patch('company-subscriptions/{company_subscription}/toggle-suspension', [CompanySubscriptionController::class, 'toggleSuspension'])->name('company-subscriptions.toggle-suspension');
        Route::resource('company-subscriptions', CompanySubscriptionController::class)->except(['destroy']);
    });

    // Módulos operativos protegidos por estado de suscripción activa de la empresa
    Route::middleware('subscription.active')->group(function () {
        // Módulos para company_admin
        Route::middleware('company_admin')->group(function () {
            // Perfil e Identidad del Taller (Logotipo y Paleta de colores)
            Route::get('company-profile', [\App\Http\Controllers\CompanyProfileController::class, 'edit'])->name('company.profile.edit');
            Route::put('company-profile', [\App\Http\Controllers\CompanyProfileController::class, 'update'])->name('company.profile.update');

            // Personal interno (Staff / Mecánicos)
            Route::patch('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
            Route::resource('staff', StaffController::class)->except(['show', 'destroy']);

            // Catálogo de Servicios
            Route::patch('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
            Route::resource('services', ServiceController::class)->except(['show', 'destroy']);

            // Clientes y Propietarios
            Route::resource('customers', CustomerController::class);

            // Flujo contextual de vehículos por cliente
            Route::get('customers/{customer}/vehicles/create', [VehicleController::class, 'createForCustomer'])->name('customers.vehicles.create');

            // Módulo general de Vehículos
            Route::resource('vehicles', VehicleController::class);
        });

        // Órdenes de Mantenimiento y Recordatorios
        Route::resource('maintenance-orders', MaintenanceOrderController::class);
        Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    });
});


