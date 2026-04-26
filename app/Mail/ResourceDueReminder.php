<?php

namespace App\Mail;

use App\Models\EmergencyResource;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResourceDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmergencyResource $resource) {}

    public function envelope(): Envelope
    {
        $name = $this->resource->name ?: $this->resource->type->label();

        return new Envelope(
            subject: 'Erinnerung: Notfall-Ressource prüfen – '.$name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.resource-due-reminder',
            with: [
                'resource' => $this->resource,
            ],
        );
    }
}
