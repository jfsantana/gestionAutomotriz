<?php

namespace App\Mail;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceOrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $portalUrl;

    public string $statusLabel;

    public ?string $previousStatusLabel;

    public string $statusMessage;

    public function __construct(
        public MaintenanceOrder $order,
        public ?string $previousStatus = null,
    ) {
        $this->portalUrl = route('portal.maintenance.index');
        $this->statusLabel = self::statusLabel($order->status);
        $this->previousStatusLabel = $previousStatus === null ? null : self::statusLabel($previousStatus);
        $this->statusMessage = match ($order->status) {
            'open' => 'Tu mantenimiento está registrado. El equipo te mantendrá al tanto de los próximos pasos.',
            'in_progress' => 'El equipo está trabajando en el mantenimiento de tu vehículo. Te avisaremos cuando cambie su estado.',
            'completed' => 'El mantenimiento ha sido completado. Contacta al taller para coordinar los siguientes pasos.',
            'cancelled' => 'Este mantenimiento fue cancelado. Si necesitas más información o deseas reprogramarlo, contacta al taller.',
        };
    }

    public function envelope(): Envelope
    {
        $event = $this->previousStatus === null ? 'Mantenimiento registrado' : 'Actualización de mantenimiento';

        return new Envelope(
            subject: "{$event}: {$this->statusLabel} | {$this->order->vehicle->plate} | {$this->order->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance_order_notification',
            text: 'emails.maintenance_order_notification_text',
        );
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'Abierta',
            'in_progress' => 'En progreso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
        };
    }
}
