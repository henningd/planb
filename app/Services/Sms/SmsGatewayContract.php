<?php

namespace App\Services\Sms;

interface SmsGatewayContract
{
    /**
     * Verschickt eine Textnachricht an genau eine Nummer.
     */
    public function send(string $to, string $text, ?string $from = null): SmsResult;

    /**
     * Ist der Versand-Backend tatsächlich konfiguriert (API-Key gesetzt etc.)?
     * Die UI gated den „Senden"-Button daran.
     */
    public function isConfigured(): bool;
}
