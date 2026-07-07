<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne Benachrichtigung an die Plattform-Kontaktadresse: Über das
 * Kontaktformular der Kommunen-Seite ist eine neue Anfrage eingegangen.
 * Reply-To zeigt auf die anfragende Person, damit direkt geantwortet
 * werden kann.
 */
class KommunenInquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->lead->source === 'kommunen' ? 'Kommunen-Anfrage: ' : 'Website-Anfrage: ').$this->lead->company_name,
            replyTo: [new Address($this->lead->email, (string) $this->lead->contact_name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.kommunen-inquiry-received',
        );
    }
}
