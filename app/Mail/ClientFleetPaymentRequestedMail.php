<?php

namespace App\Mail;

use App\Domain\Billing\Models\CustomerPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientFleetPaymentRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerPayment $payment,
        public bool $isSuperAdmin,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nueva solicitud de plan Propietario de Flota #{$this->payment->id}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.client_fleet_payment_requested',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
