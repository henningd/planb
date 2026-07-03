<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Double-Opt-In-Bestätigung für einen über den NIS2-Quick-Check
 * eingesammelten Lead. Enthält einen signierten Link, über den die Person
 * ihre E-Mail-Adresse bestätigt – erst danach wird die PDF-Auswertung
 * versendet.
 */
class Nis2QuickCheckConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Bitte bestätigen Sie Ihre NIS2-Auswertung'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nis2-quick-check-confirmation',
            with: [
                'confirmUrl' => URL::signedRoute('nis2-quick-check.confirm', ['lead' => $this->lead->getKey()]),
            ],
        );
    }
}
