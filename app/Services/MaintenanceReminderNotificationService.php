<?php

namespace App\Services;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Mail\MaintenanceReminderMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MaintenanceReminderNotificationService
{
    public const TIMEZONE = 'America/Caracas';

    public const ALREADY_SENT = 'Ya se envió un recordatorio hoy. Podrás enviarlo de nuevo mañana (hora de Caracas).';

    public const DELIVERY_ERROR = 'No se pudo enviar el recordatorio. Revisa el servicio de correo antes de reintentar; el cliente podría no haber recibido el mensaje.';

    public function unavailableReason(MaintenanceOrder $order): ?string
    {
        $order->loadMissing(['customer', 'vehicle', 'company.currentSubscription']);

        if ($order->status !== 'completed' || $order->origin !== 'workshop'
            || (! $order->next_due_date && ! $order->next_due_mileage)) {
            return 'Solo se pueden recordar mantenimientos del taller completados con un próximo vencimiento.';
        }

        if (! $order->company || ! $order->customer || ! $order->vehicle
            || (int) $order->customer->company_id !== (int) $order->company_id
            || (int) $order->vehicle->company_id !== (int) $order->company_id
            || (int) $order->vehicle->customer_id !== (int) $order->customer_id
            || $order->vehicle->customer_status !== 'ACTIVE') {
            return 'Revisa la empresa, el cliente y el vehículo: deben estar disponibles y pertenecer al mismo taller y propietario.';
        }

        $subscription = $order->company->currentSubscription;
        if ($subscription ? ! $subscription->isOperable() : ! in_array($order->company->status, ['active', 'trial'], true)) {
            return 'La empresa no tiene una suscripción operativa para enviar recordatorios.';
        }

        if (! filter_var($order->customer->email, FILTER_VALIDATE_EMAIL)) {
            return 'El cliente no tiene un email válido. Actualiza sus datos antes de enviar el recordatorio.';
        }

        return null;
    }

    public function wasSentToday(MaintenanceOrder $order): bool
    {
        return $order->reminder_sent_at?->copy()->timezone(self::TIMEZONE)->toDateString()
            === now(self::TIMEZONE)->toDateString();
    }

    public function send(MaintenanceOrder $order, bool $automatic = false): bool
    {
        return DB::transaction(function () use ($order, $automatic): bool {
            // El bloqueo de la orden serializa los envíos manuales y automáticos.
            $order = MaintenanceOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($reason = $this->unavailableReason($order)) {
                throw ValidationException::withMessages(['reminder' => $reason]);
            }

            if ($this->wasSentToday($order)) {
                return false;
            }

            if ($automatic && $order->next_due_date?->toDateString() !== now(self::TIMEZONE)->addDays(2)->toDateString()) {
                return false;
            }

            try {
                Mail::to($order->customer->email)->send(new MaintenanceReminderMail($order));
            } catch (TransportExceptionInterface $exception) {
                report($exception);

                throw $exception;
            }

            $order->reminder_sent_at = now();
            $order->save();

            return true;
        });
    }
}
