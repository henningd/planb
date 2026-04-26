<?php

namespace App\Mail;

use App\Models\HandbookTest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public HandbookTest $test) {}

    public function envelope(): Envelope
    {
        $name = $this->test->name ?: $this->test->type->label();

        return new Envelope(
            subject: 'Erinnerung: Handbuch-Test fällig – '.$name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.test-due-reminder',
            with: [
                'test' => $this->test,
            ],
        );
    }
}
