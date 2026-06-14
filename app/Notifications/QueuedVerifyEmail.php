<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queue-fähige Variante der E-Mail-Verifizierung. Verhindert, dass der
 * (langsame oder fehlschlagende) SMTP-Versand den Registrierungs-Request
 * blockiert oder abbricht – die Mail wird an den Queue-Worker übergeben.
 *
 * Der individuelle Mailinhalt kommt weiterhin aus dem global registrierten
 * VerifyEmail::toMailUsing()-Callback (siehe FortifyServiceProvider).
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
