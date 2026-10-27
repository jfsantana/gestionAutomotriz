<?php

namespace App\Services;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceReminderService
{
    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_NO_SCHEDULE = 'completed_without_schedule';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const DAYS_THRESHOLD = 30;

    public const MILEAGE_THRESHOLD = 1000;

    /**
     * Evalúa el estado del recordatorio para una orden dada.
     *
     * @return array{
     *     status: string,
     *     status_label: string,
     *     badge_class: string,
     *     date_status: ?string,
     *     mileage_status: ?string,
     *     days_remaining: ?int,
     *     mileage_remaining: ?int
     * }
     */
    public function evaluateOrder(MaintenanceOrder $order, ?int $vehicleCurrentMileage = null): array
    {
        if ($order->status !== 'completed') {
            return [
                'status' => self::STATUS_NOT_APPLICABLE,
                'status_label' => 'No Aplica',
                'badge_class' => 'secondary',
                'date_status' => null,
                'mileage_status' => null,
                'days_remaining' => null,
                'mileage_remaining' => null,
            ];
        }

        if (! $order->next_due_date && ! $order->next_due_mileage) {
            return [
                'status' => self::STATUS_NO_SCHEDULE,
                'status_label' => 'Sin Programación',
                'badge_class' => 'secondary',
                'date_status' => null,
                'mileage_status' => null,
                'days_remaining' => null,
                'mileage_remaining' => null,
            ];
        }

        $dateStatus = null;
        $daysRemaining = null;
        if ($order->next_due_date) {
            $today = Carbon::today();
            $dueDate = Carbon::parse($order->next_due_date)->startOfDay();
            $daysRemaining = (int) $today->diffInDays($dueDate, false);

            if ($daysRemaining < 0) {
                $dateStatus = self::STATUS_OVERDUE;
            } elseif ($daysRemaining <= self::DAYS_THRESHOLD) {
                $dateStatus = self::STATUS_DUE_SOON;
            } else {
                $dateStatus = self::STATUS_UPCOMING;
            }
        }

        $mileageStatus = null;
        $mileageRemaining = null;
        if ($order->next_due_mileage) {
            $currentMileage = $vehicleCurrentMileage ?? ($order->vehicle?->current_mileage ?? $order->current_mileage);
            $mileageRemaining = $order->next_due_mileage - $currentMileage;

            if ($mileageRemaining <= 0) {
                $mileageStatus = self::STATUS_OVERDUE;
            } elseif ($mileageRemaining <= self::MILEAGE_THRESHOLD) {
                $mileageStatus = self::STATUS_DUE_SOON;
            } else {
                $mileageStatus = self::STATUS_UPCOMING;
            }
        }

        // Determinar la peor condición: overdue > due_soon > upcoming
        $finalStatus = self::STATUS_UPCOMING;
        if ($dateStatus === self::STATUS_OVERDUE || $mileageStatus === self::STATUS_OVERDUE) {
            $finalStatus = self::STATUS_OVERDUE;
        } elseif ($dateStatus === self::STATUS_DUE_SOON || $mileageStatus === self::STATUS_DUE_SOON) {
            $finalStatus = self::STATUS_DUE_SOON;
        }

        return [
            'status' => $finalStatus,
            'status_label' => match ($finalStatus) {
                self::STATUS_OVERDUE => 'Vencido',
                self::STATUS_DUE_SOON => 'Por Vencer',
                default => 'Próximo',
            },
            'badge_class' => match ($finalStatus) {
                self::STATUS_OVERDUE => 'danger',
                self::STATUS_DUE_SOON => 'warning text-dark',
                default => 'success',
            },
            'date_status' => $dateStatus,
            'mileage_status' => $mileageStatus,
            'days_remaining' => $daysRemaining,
            'mileage_remaining' => $mileageRemaining,
        ];
    }

    /**
     * Obtiene el próximo mantenimiento pendiente y evaluado para un vehículo.
     */
    public function getVehicleReminder(Vehicle $vehicle, ?int $companyId = null): ?array
    {
        $latestOrder = $this->scheduledOrdersQuery($companyId)
            ->where('vehicle_id', $vehicle->id)
            ->latest('service_date')
            ->latest('id')
            ->first();

        if (! $latestOrder) {
            return null;
        }

        $evaluation = $this->evaluateOrder($latestOrder, $vehicle->current_mileage);

        return [
            'order' => $latestOrder,
            'evaluation' => $evaluation,
        ];
    }

    /**
     * Obtiene el resumen de alertas para una empresa dada.
     */
    public function getCompanySummary(?int $companyId): array
    {
        $orders = $this->scheduledOrdersQuery($companyId)
            ->with(['vehicle', 'customer'])
            ->get();

        $overdueCount = 0;
        $dueSoonCount = 0;
        $upcomingCount = 0;

        foreach ($orders as $order) {
            $eval = $this->evaluateOrder($order);
            if ($eval['status'] === self::STATUS_OVERDUE) {
                $overdueCount++;
            } elseif ($eval['status'] === self::STATUS_DUE_SOON) {
                $dueSoonCount++;
            } elseif ($eval['status'] === self::STATUS_UPCOMING) {
                $upcomingCount++;
            }
        }

        return [
            'overdue' => $overdueCount,
            'due_soon' => $dueSoonCount,
            'upcoming' => $upcomingCount,
            'total_scheduled' => $overdueCount + $dueSoonCount + $upcomingCount,
        ];
    }

    /**
     * Un companyId nulo se reserva para consultas globales del superadministrador o consola.
     *
     * @return Builder<MaintenanceOrder>
     */
    public function scheduledOrdersQuery(?int $companyId): Builder
    {
        return MaintenanceOrder::query()
            ->withConsistentOwnership()
            ->when($companyId !== null, fn (Builder $query) => $query->where('maintenance_orders.company_id', $companyId))
            ->where('status', 'completed')
            ->where(function (Builder $query): void {
                $query->whereNotNull('next_due_date')->orWhereNotNull('next_due_mileage');
            });
    }
}
