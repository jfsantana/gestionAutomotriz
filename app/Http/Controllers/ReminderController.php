<?php

namespace App\Http\Controllers;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\MaintenanceReminderNotificationService;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReminderController extends Controller
{
    public function __construct(
        protected MaintenanceReminderService $reminderService
    ) {}

    public function index(Request $request, MaintenanceReminderNotificationService $notifications): View
    {
        $currentUser = $request->user();
        abort_unless(
            in_array($currentUser->role, ['super_admin', 'company_admin', 'mechanic'], true),
            403,
            'No tienes autorización para acceder a los recordatorios del taller.'
        );

        if ($currentUser->role !== 'super_admin') {
            abort_unless($currentUser->company_id && $currentUser->company, 403, 'No tienes una empresa asociada disponible.');
        }

        $companyId = $currentUser->role === 'super_admin' ? null : $currentUser->company_id;
        $statusFilter = $request->input('status');
        $searchPlate = $request->input('plate');
        $searchCustomer = $request->input('customer');

        $query = $this->reminderService->scheduledOrdersQuery($companyId)
            ->with(['customer', 'vehicle', 'company.currentSubscription']);

        if ($searchPlate) {
            $query->whereHas('vehicle', fn ($q) => $q->where('plate', 'like', "%{$searchPlate}%"));
        }

        if ($searchCustomer) {
            $query->whereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$searchCustomer}%"));
        }

        $allOrders = $query->latest('service_date')->get();

        // Evaluar cada orden y adjuntar su estado calculado
        $evaluatedItems = $allOrders->map(function ($order) use ($notifications) {
            $evaluation = $this->reminderService->evaluateOrder($order);
            $order->reminder = $evaluation;
            $order->reminder_email_error = $notifications->unavailableReason($order)
                ?? ($notifications->wasSentToday($order) ? MaintenanceReminderNotificationService::ALREADY_SENT : null);

            return $order;
        });

        // Filtrar por estado si se seleccionó en el formulario
        if ($statusFilter) {
            $evaluatedItems = $evaluatedItems->filter(function ($order) use ($statusFilter) {
                return $order->reminder['status'] === $statusFilter;
            });
        }

        // Paginación manual para colección calculada
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $pagedData = $evaluatedItems->slice(($page - 1) * $perPage, $perPage)->values();

        $reminders = new LengthAwarePaginator(
            $pagedData,
            $evaluatedItems->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $summary = $this->reminderService->getCompanySummary($companyId);

        return view('reminders.index', compact('reminders', 'summary', 'statusFilter', 'searchPlate', 'searchCustomer'));
    }

    public function send(MaintenanceOrder $order, MaintenanceReminderNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('send-maintenance-reminder', $order);

        try {
            $sent = $notifications->send($order);
        } catch (TransportExceptionInterface $exception) {
            return back()->with('error', MaintenanceReminderNotificationService::DELIVERY_ERROR);
        }

        return back()->with(
            $sent ? 'success' : 'info',
            $sent ? 'Recordatorio enviado al correo del cliente.' : MaintenanceReminderNotificationService::ALREADY_SENT
        );
    }
}
