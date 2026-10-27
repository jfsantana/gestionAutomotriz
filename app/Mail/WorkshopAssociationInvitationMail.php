<?php

namespace App\Mail;

use App\Domain\Companies\Models\WorkshopAssociationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkshopAssociationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptanceUrl;

    public function __construct(
        public WorkshopAssociationInvitation $invitation,
        string $plainToken,
    ) {
        $this->invitation->loadMissing(['customer', 'company']);
        $this->acceptanceUrl = route('workshop-associations.show', ['token' => $plainToken]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitación para asociarte a {$this->invitation->company->name} en AutoBitácora",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.workshop_association_invitation',
        );
    }
}
