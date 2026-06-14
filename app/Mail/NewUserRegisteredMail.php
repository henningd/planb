<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Interne Benachrichtigung an die Betreiber-Adressen (BCC), dass sich ein
 * neuer Nutzer registriert hat. Die Empfänger stehen in
 * config('mail.register_bcc') (aus MAIL_REGISTER_BCC, kommagetrennt) und
 * werden ausschließlich als BCC adressiert, damit sie sich gegenseitig nicht
 * sehen.
 *
 * @param  list<string>  $recipients
 */
class NewUserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $recipients
     */
    public function __construct(public User $user, public array $recipients) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Neue Registrierung: :name', ['name' => $this->user->name]),
            bcc: $this->recipients,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-user-registered',
            with: [
                'user' => $this->user,
            ],
        );
    }
}
