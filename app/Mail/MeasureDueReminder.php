<?php

namespace App\Mail;

use App\Models\PreventiveMeasure;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeasureDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PreventiveMeasure $measure) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erinnerung: Präventivmaßnahme fällig – '.$this->measure->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.measure-due-reminder',
            with: [
                'measure' => $this->measure,
            ],
        );
    }
}
