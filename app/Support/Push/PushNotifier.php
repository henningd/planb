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
        $dead = $this->sender->send($this->tokensFor($company), ['type' => 'sync']);
        $this->pruneDeadTokens($dead);
    }

    /**
     * Sichtbare Alarmierung an alle Geräte der Firma – Tippen öffnet das Szenario.
     * Das Gerät des Auslösers wird optional ausgeschlossen ({@see $excludeUserId}),
     * damit dieser keinen Push zu seinem eigenen, gerade sichtbaren Alarm erhält.
     */
    public function incident(Company $company, string $scenarioId, string $scenarioTitle, ?int $excludeUserId = null): void
    {
        $dead = $this->sender->send(
            $this->tokensFor($company, $excludeUserId),
            ['type' => 'incident', 'scenario_id' => $scenarioId],
            'Notfall gemeldet',
            $scenarioTitle,
        );
        $this->pruneDeadTokens($dead);
    }

    /**
     * Sichtbare Benachrichtigung, dass ein Notfall beendet/abgebrochen wurde.
     * Das `type=incident_ended` veranlasst die Apps zusätzlich zum Neu-Sync, damit
     * die „Aktiver Notfall"-Karte verschwindet.
     */
    public function incidentEnded(Company $company, string $title, string $outcome, ?int $excludeUserId = null): void
    {
        $heading = $outcome === 'aborted' ? 'Notfall abgebrochen' : 'Notfall beendet';

        $dead = $this->sender->send(
            $this->tokensFor($company, $excludeUserId),
            ['type' => 'incident_ended'],
            $heading,
            $title,
        );
        $this->pruneDeadTokens($dead);
    }

    /**
     * @param  int|null  $excludeUserId  Geräte dieses Users ausschließen (z. B. der Auslöser
     *                                   eines sichtbaren Alarms). Nur für sichtbare Pushes.
     * @return list<string>
     */
    private function tokensFor(Company $company, ?int $excludeUserId = null): array
    {
        return MobileDevice::query()
            ->where('company_id', $company->id)
            ->when($excludeUserId !== null, fn ($query) => $query->where('user_id', '!=', $excludeUserId))
            ->pluck('fcm_token')
            ->all();
    }

    /**
     * Räumt Geräte mit von FCM als ungültig gemeldeten Tokens auf.
     *
     * @param  list<string>  $dead
     */
    private function pruneDeadTokens(array $dead): void
    {
        if ($dead === []) {
            return;
        }

        MobileDevice::query()->whereIn('fcm_token', $dead)->delete();
    }
}
