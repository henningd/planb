<?php

namespace App\Services\Sms;

/**
 * Fallback-Gateway für lokale Umgebungen ohne API-Key. `send()` schreibt
 * den Versuch ins Log und liefert ein Erfolgs-Result, damit die UI den
 * Flow ohne Provider-Kosten testen kann.
 */
class NullSmsGateway implements SmsGatewayContract
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(string $to, string $text, ?string $from = null): SmsResult
    {
        logger()->info('SMS (Null-Gateway, kein Versand)', [
            'to' => $to,
            'from' => $from,
            'text' => $text,
        ]);

        return SmsResult::ok($to);
    }
}
