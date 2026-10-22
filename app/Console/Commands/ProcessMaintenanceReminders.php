<?php

namespace App\Console\Commands;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\MaintenanceReminderService;
use Illuminate\Console\Command;

class ProcessMaintenanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:process-reminders {--company= : ID de la empresa a evaluar (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evalúa y procesa los recordatorios de mantenimiento próximos a vencer y vencidos.';

    /**
     * Execute the console command.
     */
    public function handle(MaintenanceReminderService $reminderService): int
    {
        $companyId = $this->option('company');

        $this->info('Evaluando recordatorios de mantenimiento...');

        $orders = MaintenanceOrder::query()
            ->with(['customer', 'vehicle', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'completed')
            ->where(function ($q) {
                $q->whereNotNull('next_due_date')
                    ->orWhereNotNull('next_due_mileage');
            })
            ->latest('service_date')
            ->get();

        $rows = [];
        $overdue = 0;
        $dueSoon = 0;

        foreach ($orders as $order) {
            $eval = $reminderService->evaluateOrder($order);

            if ($eval['status'] === MaintenanceReminderService::STATUS_OVERDUE) {
                $overdue++;
            } elseif ($eval['status'] === MaintenanceReminderService::STATUS_DUE_SOON) {
                $dueSoon++;
            }

            $rows[] = [
                $order->order_number ?? '#' . $order->id,
                $order->company?->name,
                $order->customer?->full_name,
                $order->vehicle?->plate,
                $order->service_name_snapshot,
                $order->next_due_date?->format('Y-m-d') ?? '—',
                $order->next_due_mileage ? number_format($order->next_due_mileage) . ' km' : '—',
                $order->vehicle?->current_mileage ? number_format($order->vehicle->current_mileage) . ' km' : '—',
                strtoupper($eval['status']),
            ];
        }

        $this->table(
            ['Orden', 'Empresa', 'Cliente', 'Placa', 'Servicio', 'Vence Fecha', 'Vence Km', 'Km Actual', 'Estado'],
            $rows
        );

        $this->info("Resumen: {$overdue} Vencidos | {$dueSoon} Por Vencer | Total Evaluados: " . count($orders));
        $this->comment('Base preparada para despachar notificaciones automáticas por email o canales configurados.');

        return Command::SUCCESS;
    }
}
