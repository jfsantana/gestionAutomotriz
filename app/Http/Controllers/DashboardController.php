<?php

namespace App\Http\Controllers;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\Health\SystemHealthService;
use App\Services\MaintenanceReminderService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
    public function index(Request $request): View|RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser?->role === 'company_admin') {
            return redirect()->route('company.dashboard');
        }

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

    /**
     * Muestra la vista del Dashboard para Usuario de Empresa.
     */
    public function company(Request $request): View
    {
        $currentUser = $request->user();
        $company = $currentUser?->company;
        $companyId = $currentUser?->company_id;

        $customersCount = $company ? $company->customers()->count() : 0;

        $openOrdersCount = $companyId
            ? MaintenanceOrder::where('company_id', $companyId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count()
            : 0;

        $vehiclesCount = $company ? $company->vehicles()->count() : 0;

        $reminderSummary = $companyId ? $this->reminderService->getCompanySummary($companyId) : [];
        $serviceAlertsCount = ($reminderSummary['overdue'] ?? 0) + ($reminderSummary['due_soon'] ?? 0);

        // Próximos mantenimientos a 7 días o próximos por kilometraje
        $upcomingReminders = new LengthAwarePaginator([], 0, 5);

        if ($companyId) {
            $orders = MaintenanceOrder::query()
                ->with(['customer', 'vehicle', 'service'])
                ->where('company_id', $companyId)
                ->where('status', 'completed')
                ->where(function ($q) {
                    $q->whereNotNull('next_due_date')
                        ->orWhereNotNull('next_due_mileage');
                })
                ->latest('service_date')
                ->get();

            $filteredReminders = $orders->map(function ($order) {
                $order->reminder = $this->reminderService->evaluateOrder($order);

                return $order;
            })->filter(function ($order) {
                $rem = $order->reminder;

                // 1. Vencimiento por fecha en los próximos 7 días (o vencido)
                $isDueByDate = $rem['days_remaining'] !== null && $rem['days_remaining'] <= 7;

                // 2. Vencimiento por kilometraje (alcanzado o dentro del umbral de 1,000 km)
                $isDueByMileage = $rem['mileage_remaining'] !== null && $rem['mileage_remaining'] <= MaintenanceReminderService::MILEAGE_THRESHOLD;

                return $isDueByDate || $isDueByMileage;
            })->sortBy(function ($order) {
                // Ordenar dando prioridad a los días restantes si existen
                return $order->reminder['days_remaining'] ?? 99999;
            })->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 5;
            $items = $filteredReminders->slice(($page - 1) * $perPage, $perPage)->values();

            $upcomingReminders = new LengthAwarePaginator(
                $items,
                $filteredReminders->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('dashboard.company', [
            'customersCount' => $customersCount,
            'openOrdersCount' => $openOrdersCount,
            'vehiclesCount' => $vehiclesCount,
            'serviceAlertsCount' => $serviceAlertsCount,
            'upcomingReminders' => $upcomingReminders,
        ]);
    }
}
