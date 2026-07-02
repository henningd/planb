<?php

namespace App\Support;

/**
 * Ergebnis eines Benachrichtigungsversands aus einer Aufgabe/Maßnahme/Prüfung:
 * wie viele E-Mails gingen an wen. Wird von der UI für die Toast-Rückmeldung genutzt.
 */
final class TaskNotificationResult
{
    /**
     * @param  array<int, string>  $recipients  Namen der benachrichtigten Personen
     */
    public function __construct(
        public readonly int $sent,
        public readonly array $recipients,
    ) {}

    public function isEmpty(): bool
    {
        return $this->sent === 0;
    }

    public function names(): string
    {
        return implode(', ', $this->recipients);
    }
}
