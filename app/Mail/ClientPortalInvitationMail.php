<?php

namespace App\Mail;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientPortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $activationUrl;

    public function __construct(
        public Customer $customer,
        public Company $company,
        public string $plainToken
    ) {
        $this->activationUrl = route('portal.activate.show', ['token' => $plainToken]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bienvenido a AutoBitacora - Activa tu acceso al portal de tu vehículo ({$this->company->name})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client_invitation',
        );
    }
}
