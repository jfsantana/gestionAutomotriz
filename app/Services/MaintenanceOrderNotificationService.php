<?php

namespace App\Services;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Mail\MaintenanceOrderNotificationMail;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MaintenanceOrderNotificationService
{
    /**
     * Devuelve una advertencia cuando no se pudo notificar a todos los destinatarios.
     */
    public function send(MaintenanceOrder $order, ?string $previousStatus = null): ?string
    {
        if ($order->origin === 'customer') {
            return null;
        }

        $order->loadMissing(['customer', 'vehicle', 'company']);

        if (! $order->customer || ! filter_var($order->customer->email, FILTER_VALIDATE_EMAIL)) {
            return 'El mantenimiento quedó guardado, pero no se envió el correo porque el propietario no tiene un email válido. Actualiza los datos del cliente.';
        }

        if (! $order->company || ! $order->vehicle) {
            return 'El mantenimiento quedó guardado, pero no se pudo enviar la notificación. Revisa la empresa y el vehículo asociados.';
        }

        $customerEmail = $order->customer->email;
        $adminEmails = $order->company->admins()
            ->where('is_active', true)
            ->pluck('email')
            ->filter(fn (?string $email): bool => (bool) filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique(fn (string $email): string => strtolower($email));
        $copies = $adminEmails
            ->reject(fn (string $email): bool => strcasecmp($email, $customerEmail) === 0)
            ->values()
            ->all();

        try {
            Mail::to($customerEmail)
                ->cc($copies)
                ->send(new MaintenanceOrderNotificationMail($order, $previousStatus));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return 'El mantenimiento quedó guardado, pero no se pudo enviar el correo. Revisa la configuración del servicio de correo; el cliente podría no haber recibido la notificación.';
        }

        if ($adminEmails->isEmpty()) {
            return 'Se envió el correo al propietario, pero sin copia: la empresa no tiene un administrador activo con email válido.';
        }

        return null;
    }
}
