<?php

namespace App\Support\Push;

use App\Jobs\SendCompanyPush;
use App\Models\Company;

/**
 * Fachliche Push-Auslöser der Notfall-App. Löst je Firma die passende Nachricht
 * aus und übergibt den eigentlichen Versand an einen queuebaren
 * {@see SendCompanyPush}-Job, damit das Auslösen nicht auf die FCM-HTTP-Calls
 * wartet. Token-Auflösung und Aufräumen toter Tokens passieren im Job.
 */
class PushNotifier
{
    /**
     * Stiller „bitte jetzt synchronisieren"-Push an alle Geräte der Firma.
     * Dadurch aktualisieren sich die Apps sofort, statt erst beim nächsten
     * Intervall/Foreground.
     */
    public function syncCompany(Company $company): void
    {
        SendCompanyPush::dispatch($company->id, ['type' => 'sync']);
    }

    /**
     * Sichtbare Alarmierung an alle Geräte der Firma – Tippen öffnet das Szenario.
     * Das Gerät des Auslösers wird optional ausgeschlossen ({@see $excludeUserId}),
     * damit dieser keinen Push zu seinem eigenen, gerade sichtbaren Alarm erhält.
     */
    public function incident(Company $company, string $scenarioId, string $scenarioTitle, ?int $excludeUserId = null): void
    {
        SendCompanyPush::dispatch(
            $company->id,
            ['type' => 'incident', 'scenario_id' => $scenarioId],
            'Notfall gemeldet',
            $scenarioTitle,
            $excludeUserId,
        );
    }

    /**
     * Sichtbare Benachrichtigung, dass ein Notfall beendet/abgebrochen wurde.
     * Das `type=incident_ended` veranlasst die Apps zusätzlich zum Neu-Sync, damit
     * die „Aktiver Notfall"-Karte verschwindet.
     */
    public function incidentEnded(Company $company, string $title, string $outcome, ?int $excludeUserId = null): void
    {
        $heading = $outcome === 'aborted' ? 'Notfall abgebrochen' : 'Notfall beendet';

        SendCompanyPush::dispatch(
            $company->id,
            ['type' => 'incident_ended'],
            $heading,
            $title,
            $excludeUserId,
        );
    }

    /**
     * Sichtbare Benachrichtigung, dass ein neues Notfallhandbuch freigegeben wurde.
     * Das `type=handbook_released` erlaubt den Apps, gezielt auf das Handbuch zu
     * verweisen.
     */
    public function handbookReleased(Company $company, string $version): void
    {
        SendCompanyPush::dispatch(
            $company->id,
            ['type' => 'handbook_released'],
            'Neues Notfallhandbuch',
            $version,
        );
    }
}
