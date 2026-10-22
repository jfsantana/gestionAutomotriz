<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
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

    // Módulo de Companies (exclusivo para super_admin)
    Route::middleware('super_admin')->group(function () {
        Route::resource('companies', CompanyController::class);
    });
});


