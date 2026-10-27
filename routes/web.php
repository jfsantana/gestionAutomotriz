<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientActivationController;
use App\Http\Controllers\ClientSubscriptionController;
use App\Http\Controllers\CompanyAdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompanySubscriptionController;
use App\Http\Controllers\CompanySubscriptionPaymentController;
use App\Http\Controllers\CompanySubscriptionStatusController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MaintenanceOrderController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\Portal\ClientPortalController;
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

    // Activación y Creación de Contraseña del Cliente Invitado (Vía Token de Correo)
    Route::get('portal/activate/{token}', [ClientActivationController::class, 'show'])->name('portal.activate.show');
    Route::post('portal/activate/{token}', [ClientActivationController::class, 'activate'])->name('portal.activate.submit');
});

// Rutas protegidas (autenticadas)
Route::middleware('auth')->group(function () {
    // Pantalla de Aceptación Obligatoria de Políticas (vía middleware EnsurePolicyAccepted)
    Route::get('policies/terms-and-conditions', [PolicyController::class, 'show'])->name('policy.show');
    Route::post('policies/accept', [PolicyController::class, 'accept'])->name('policy.accept');

    Route::get('/', [DashboardController::class, 'index'])->middleware(['workshop', 'subscription.active'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['workshop', 'subscription.active'])->name('dashboard');

    // Dashboard temporal para Usuario de Empresa
    Route::get('/company/dashboard', [DashboardController::class, 'company'])->middleware(['workshop', 'subscription.active'])->name('company.dashboard');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Estado de cuenta y suscripción (accesible siempre para ver estatus / pantalla de bloqueo)
    Route::get('subscription-status', [CompanySubscriptionStatusController::class, 'show'])->name('subscription.status');
    Route::post('company-subscription-payment-requests', [CompanySubscriptionPaymentController::class, 'store'])
        ->middleware(['workshop', 'company_admin'])
        ->name('company-subscription-payments.store');

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

        // Solicitudes y confirmación de pagos de clientes finales
        Route::get('client-subscriptions', [ClientSubscriptionController::class, 'index'])->name('client-subscriptions.index');
        Route::patch('client-subscriptions/{customer_payment}/confirm', [ClientSubscriptionController::class, 'confirmPayment'])->name('client-subscriptions.confirm');
        Route::delete('client-subscriptions/{customer_payment}', [ClientSubscriptionController::class, 'hideRequest'])->name('client-subscriptions.hide');
        Route::patch('client-subscriptions/{customer_payment}/reverse', [ClientSubscriptionController::class, 'reversePayment'])->name('client-subscriptions.reverse');

        // Solicitudes de renovación de talleres y concesionarios
        Route::patch('company-subscription-payments/{company_payment_request}/confirm', [CompanySubscriptionPaymentController::class, 'confirm'])->name('company-subscription-payments.confirm');
        Route::delete('company-subscription-payments/{company_payment_request}', [CompanySubscriptionPaymentController::class, 'hide'])->name('company-subscription-payments.hide');
    });

    // Módulos operativos protegidos por estado de suscripción activa de la empresa
    Route::middleware(['workshop', 'subscription.active'])->group(function () {
        // Módulos para company_admin
        Route::middleware('company_admin')->group(function () {
            // Perfil e Identidad del Taller (Logotipo y Paleta de colores)
            Route::get('company-profile', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
            Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company.profile.update');

            // Personal interno (Staff / Mecánicos)
            Route::patch('staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
            Route::resource('staff', StaffController::class)->except(['show', 'destroy']);

            // Catálogo de Servicios
            Route::patch('services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])->name('services.toggle-status');
            Route::resource('services', ServiceController::class)->except(['show', 'destroy']);

            // Clientes y Propietarios
            Route::post('reminders/{order}/send', [ReminderController::class, 'send'])
                ->middleware('throttle:10,1')
                ->name('reminders.send');
            Route::post('customers/{customer}/invite', [CustomerInvitationController::class, 'send'])->name('customers.invite');
            Route::resource('customers', CustomerController::class);

            // Flujo contextual de vehículos por cliente
            Route::get('customers/{customer}/vehicles/create', [VehicleController::class, 'createForCustomer'])->name('customers.vehicles.create');

            // Módulo general de Vehículos y Acción de Baja por Privacidad
            Route::post('vehicles/{vehicle}/decommission', [VehicleController::class, 'decommission'])->name('vehicles.decommission');
            Route::resource('vehicles', VehicleController::class);
        });

        // Órdenes de Mantenimiento y Recordatorios
        Route::resource('maintenance-orders', MaintenanceOrderController::class);
        Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    });

    // ==========================================
    // PORTAL EXCLUSIVO PARA CLIENTES (Rol: customer)
    // ==========================================
    Route::middleware('customer.only')->prefix('portal')->name('portal.')->group(function () {
        // 1. Inicio / Resumen
        Route::get('/', [ClientPortalController::class, 'dashboard'])->name('dashboard');

        // 2. Mis Vehículos y Baja Lógica
        Route::get('vehicles', [ClientPortalController::class, 'vehicles'])->name('vehicles.index');
        Route::post('customers/{customer}/upgrade-to-fleet', [ClientSubscriptionController::class, 'upgradeToFleet'])
            ->middleware('throttle:5,1')
            ->name('customers.upgrade-to-fleet');
        Route::get('vehicles/{vehicle}', [ClientPortalController::class, 'vehicleShow'])->name('vehicles.show');
        Route::post('vehicles/{vehicle}/decommission', [ClientPortalController::class, 'decommissionVehicle'])->name('vehicles.decommission');

        // 3. Historial de Mantenimientos
        Route::get('maintenance', [ClientPortalController::class, 'maintenanceHistory'])->name('maintenance.index');

        // 4. Registrar Mantenimiento Propio / Personal
        Route::get('maintenance/create-personal', [ClientPortalController::class, 'createPersonalMaintenance'])->name('maintenance.create');
        Route::post('maintenance/create-personal', [ClientPortalController::class, 'storePersonalMaintenance'])->name('maintenance.store');

        // 5. Mi Cuenta y Seguridad
        Route::get('account', [ClientPortalController::class, 'account'])->name('account');
        Route::put('account', [ClientPortalController::class, 'updateAccount'])->name('account.update');
        Route::put('account/password', [ClientPortalController::class, 'updatePassword'])->name('account.password');
        Route::post('account/deactivate', [ClientPortalController::class, 'deactivateAccount'])->name('account.deactivate');
    });
});
