<?php

namespace App\Support\Push;

use App\Models\Company;
use App\Models\MobileDevice;

/**
 * Fachliche Push-Auslöser der Notfall-App. Löst je Firma die passenden
 * Geräte-Tokens auf und übergibt die Nachricht an den {@see PushSender}.
 */
class PushNotifier
{
    public function __construct(private readonly PushSender $sender) {}

    /**
     * Stiller „bitte jetzt synchronisieren"-Push an alle Geräte der Firma.
     * Dadurch aktualisieren sich die Apps sofort, statt erst beim nächsten
     * Intervall/Foreground.
     */
    public function syncCompany(Company $company): void
    {
        $this->sender->send($this->tokensFor($company), ['type' => 'sync']);
    }

    /**
     * Sichtbare Alarmierung an alle Geräte der Firma – Tippen öffnet das Szenario.
     */
    public function incident(Company $company, string $scenarioId, string $scenarioTitle): void
    {
        $this->sender->send(
            $this->tokensFor($company),
            ['type' => 'incident', 'scenario_id' => $scenarioId],
            'Notfall gemeldet',
            $scenarioTitle,
        );
    }

    /**
     * @return list<string>
     */
    private function tokensFor(Company $company): array
    {
        return MobileDevice::query()
            ->where('company_id', $company->id)
            ->pluck('fcm_token')
            ->all();
    }
}
