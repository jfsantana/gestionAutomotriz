<?php

namespace App\Http\Controllers;

use App\Services\Health\SystemHealthService;
use App\Services\MaintenanceReminderService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controlador de la vista principal del Dashboard institucional/técnico.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected SystemHealthService $healthService,
        protected MaintenanceReminderService $reminderService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Muestra la pantalla del Dashboard con el estado del sistema, alertas operativas y suscripciones.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        // Si un cliente intenta entrar al dashboard del taller, redirigir a su portal o abortar
        if ($currentUser && $currentUser->role === 'customer') {
            abort(403, 'Los clientes no tienen acceso al panel administrativo del taller.');
        }

        $systemHealth = $this->healthService->check();

        $companyId = $currentUser && $currentUser->role !== 'super_admin'
            ? $currentUser->company_id
            : null;

        $reminderSummary = $this->reminderService->getCompanySummary($companyId);

        $subscriptionSummary = $currentUser && $currentUser->role === 'super_admin'
            ? $this->subscriptionService->getSuperAdminSummary()
            : null;

        $companySubscription = $currentUser && $currentUser->company
            ? $currentUser->company->currentSubscription
            : null;

        return view('dashboard.index', [
            'systemHealth' => $systemHealth,
            'reminderSummary' => $reminderSummary,
            'subscriptionSummary' => $subscriptionSummary,
            'companySubscription' => $companySubscription,
        ]);
    }
}
