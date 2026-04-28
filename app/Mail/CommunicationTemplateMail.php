<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommunicationTemplateMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $resolvedSubject,
        public string $resolvedBody,
        public string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolvedSubject !== '' ? $this->resolvedSubject : __('Information aus :company', ['company' => $this->companyName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.communication-template',
            with: [
                'body' => $this->resolvedBody,
                'companyName' => $this->companyName,
            ],
        );
    }
}
