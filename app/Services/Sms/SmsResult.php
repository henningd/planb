<?php

namespace App\Services\Sms;

/**
 * Ergebnis eines SMS-Versuchs für genau eine Empfängernummer. `success`
 * ist true, wenn der Provider die Nachricht akzeptiert hat — sagt nichts
 * über die spätere Zustellung am Endgerät aus.
 */
final class SmsResult
{
    public function __construct(
        public readonly string $to,
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(string $to, ?string $messageId = null): self
    {
        return new self($to, true, $messageId);
    }

    public static function fail(string $to, string $reason): self
    {
        return new self($to, false, null, $reason);
    }
}
