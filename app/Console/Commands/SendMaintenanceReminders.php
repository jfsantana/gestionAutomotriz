<?php

namespace App\Console\Commands;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\MaintenanceReminderNotificationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendMaintenanceReminders extends Command
{
    protected $signature = 'maintenance:send-reminders {--company= : ID de la empresa (opcional)}';

    protected $description = 'Envía recordatorios dos días antes del vencimiento, una vez al día por mantenimiento.';

    public function handle(MaintenanceReminderNotificationService $notifications): int
    {
        $companyId = $this->option('company') === null ? null : (string) $this->option('company');
        if ($companyId !== null && (! ctype_digit($companyId) || (int) $companyId < 1)) {
            $this->error('El ID de empresa debe ser un entero positivo.');

            return self::FAILURE;
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $dueDate = now(MaintenanceReminderNotificationService::TIMEZONE)->addDays(2);

        MaintenanceOrder::query()
            ->where('status', 'completed')
            ->where('origin', 'workshop')
            ->where('next_due_date', '>=', $dueDate->toDateString())
            ->where('next_due_date', '<', $dueDate->copy()->addDay()->toDateString())
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->chunkById(100, function ($orders) use ($notifications, &$sent, &$skipped, &$failed): void {
                /** @var MaintenanceOrder $order */
                foreach ($orders as $order) {
                    try {
                        if ($notifications->send($order, automatic: true)) {
                            $sent++;
                        } else {
                            $skipped++;
                            $this->line("Orden #{$order->id}: ya notificada hoy o vencimiento modificado.");
                        }
                    } catch (ValidationException $exception) {
                        $failed++;
                        $this->error("Orden #{$order->id}: ".implode(' ', $exception->errors()['reminder']));
                    } catch (TransportExceptionInterface $exception) {
                        $failed++;
                        $this->error("Orden #{$order->id}: ".MaintenanceReminderNotificationService::DELIVERY_ERROR);
                    }
                }
            });

        $this->info("Enviados: {$sent} | Omitidos: {$skipped} | Fallidos: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
