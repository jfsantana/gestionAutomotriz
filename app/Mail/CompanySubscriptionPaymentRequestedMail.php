<?php

namespace App\Mail;

use App\Domain\Billing\Models\CompanyPaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanySubscriptionPaymentRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CompanyPaymentRequest $paymentRequest,
        public bool $isSuperAdmin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nueva solicitud de renovación de taller #{$this->paymentRequest->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company_subscription_payment_requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
