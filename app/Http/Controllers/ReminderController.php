<?php

namespace App\Http\Controllers;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function __construct(
        protected MaintenanceReminderService $reminderService
    ) {}

    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $statusFilter = $request->input('status');
        $searchPlate = $request->input('plate');
        $searchCustomer = $request->input('customer');

        $query = MaintenanceOrder::query()
            ->with(['customer', 'vehicle', 'company', 'mechanic'])
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNotNull('next_due_date')
                    ->orWhereNotNull('next_due_mileage');
            });

        if (in_array($currentUser->role, ['company_admin', 'mechanic'], true)) {
            $query->where('company_id', $currentUser->company_id);
        }

        if ($searchPlate) {
            $query->whereHas('vehicle', fn ($q) => $q->where('plate', 'like', "%{$searchPlate}%"));
        }

        if ($searchCustomer) {
            $query->whereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$searchCustomer}%"));
        }

        $allOrders = $query->latest('service_date')->get();

        // Evaluar cada orden y adjuntar su estado calculado
        $evaluatedItems = $allOrders->map(function ($order) {
            $evaluation = $this->reminderService->evaluateOrder($order);
            $order->reminder = $evaluation;
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

        $summary = $this->reminderService->getCompanySummary(
            $currentUser->role === 'super_admin' ? null : $currentUser->company_id
        );

        return view('reminders.index', compact('reminders', 'summary', 'statusFilter', 'searchPlate', 'searchCustomer'));
    }
}
