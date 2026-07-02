<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Benachrichtigt die verantwortliche Person, dass ihr eine Aufgabe,
 * Präventivmaßnahme oder Prüfung zugewiesen wurde. Optional mit einer
 * Kalender-Einladung (`termin.ics`) als Anhang.
 */
class TaskAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $sourceLabel,
        public string $title,
        public ?string $description,
        public string $dueLabel,
        public ?string $intervalLabel,
        public ?string $actionUrl,
        public ?string $ics = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sourceLabel.' zugewiesen: '.$this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.task-assignment',
            with: [
                'recipientName' => $this->recipientName,
                'sourceLabel' => $this->sourceLabel,
                'title' => $this->title,
                'description' => $this->description,
                'dueLabel' => $this->dueLabel,
                'intervalLabel' => $this->intervalLabel,
                'actionUrl' => $this->actionUrl,
                'hasCalendar' => $this->ics !== null,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->ics === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->ics, 'termin.ics')
                ->withMime('text/calendar; charset=utf-8; method=REQUEST'),
        ];
    }
}
