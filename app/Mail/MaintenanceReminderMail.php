<?php

namespace App\Mail;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Services\MaintenanceReminderNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $portalUrl;

    public string $timingMessage;

    public function __construct(public MaintenanceOrder $order)
    {
        $this->portalUrl = route('portal.maintenance.index');
        $days = $order->next_due_date
            ? (int) now(MaintenanceReminderNotificationService::TIMEZONE)->startOfDay()
                ->diffInDays(Carbon::parse($order->next_due_date->format('Y-m-d'), MaintenanceReminderNotificationService::TIMEZONE), false)
            : null;

        $this->timingMessage = match (true) {
            $days === null => 'Tu próximo mantenimiento está programado por kilometraje.',
            $days < 0 => 'La fecha recomendada de tu mantenimiento ya pasó.',
            $days === 0 => 'Tu mantenimiento vence hoy.',
            $days === 1 => 'Tu mantenimiento vence mañana.',
            default => "Tu mantenimiento vence en {$days} días.",
        };
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio de mantenimiento | {$this->order->vehicle->plate} | {$this->order->company->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance_reminder',
            text: 'emails.maintenance_reminder_text',
        );
    }
}
