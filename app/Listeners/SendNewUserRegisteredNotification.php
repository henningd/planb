<?php

namespace App\Listeners;

use App\Mail\NewUserRegisteredMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

/**
 * Schickt bei jeder Registrierung eine interne BCC-Benachrichtigung an die in
 * MAIL_REGISTER_BCC (config('mail.register_bcc')) hinterlegten Adressen.
 *
 * Auto-registriert über Laravels Listener-Discovery (app/Listeners), gekoppelt
 * an das typisierte Event im ersten Parameter von handle().
 */
class SendNewUserRegisteredNotification
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        /** @var list<string> $recipients */
        $recipients = array_values(array_filter((array) config('mail.register_bcc', [])));

        if ($recipients === []) {
            return;
        }

        Mail::send(new NewUserRegisteredMail($event->user, $recipients));
    }
}
