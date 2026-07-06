<?php

namespace App\Services\Chat;

use App\Jobs\SendAlarmChatPost;
use App\Models\Company;
use App\Support\Push\PushNotifier;
use App\Support\Settings\CompanySetting;
use Throwable;

/**
 * Fachliche Chat-Auslöser der Alarm-Kette: postet Notfall-Start, Eskalation
 * und Entwarnung als Karte in die konfigurierten Slack-/Teams-Kanäle der
 * Firma (beide, wenn beide Webhook-URLs gesetzt sind). Titel und Präfix
 * („ÜBUNG: ") entsprechen den System-Pushes des {@see PushNotifier}.
 *
 * Gepostet wird nur, wenn das Company-Setting `chat_alarm_posts_enabled`
 * aktiv ist UND mindestens eine Webhook-URL hinterlegt wurde. Der Versand
 * läuft als queued Job ({@see SendAlarmChatPost}) und ist strikt
 * best-effort — Fehler dürfen das Auslösen eines Alarms nie blockieren.
 */
class AlarmChatNotifier
{
    /**
     * Karte zum Notfall-Start; bei Übungen mit sichtbarem „ÜBUNG: "-Präfix.
     */
    public function incidentStarted(Company $company, string $scenarioTitle, ?string $startedBy, bool $isDrill = false): void
    {
        $body = $scenarioTitle;

        if (filled($startedBy)) {
            $body .= "\nAusgelöst von: ".$startedBy;
        }

        $this->post($company, ($isDrill ? 'ÜBUNG: ' : '').'Notfall gemeldet', $body);
    }

    /**
     * Karte zur Eskalation eines unquittierten echten Alarms. Übungen werden
     * nie eskaliert, daher kein Drill-Zweig.
     */
    public function incidentUnacknowledged(Company $company, string $runTitle): void
    {
        $this->post($company, 'Notfall unbestätigt!', 'Noch niemand hat den Notfall übernommen: '.$runTitle);
    }

    /**
     * Karte zur Entwarnung — Ausgang „beendet" oder „abgebrochen".
     */
    public function incidentEnded(Company $company, string $runTitle, string $outcome, bool $isDrill = false): void
    {
        $heading = ($isDrill ? 'ÜBUNG: ' : '').($outcome === 'aborted' ? 'Notfall abgebrochen' : 'Notfall beendet');
        $body = $runTitle."\nAusgang: ".($outcome === 'aborted' ? 'abgebrochen' : 'beendet');

        $this->post($company, $heading, $body);
    }

    /**
     * Prüft Setting + Webhook-URLs und übergibt den eigentlichen Versand an
     * einen queued Job. Darf nie werfen — die Alarm-Kette geht immer vor.
     */
    private function post(Company $company, string $title, string $body): void
    {
        try {
            $settings = CompanySetting::for($company);

            if (! (bool) $settings->get('chat_alarm_posts_enabled', true)) {
                return;
            }

            $slackUrl = trim((string) $settings->get('slack_webhook_url', ''));
            $teamsUrl = trim((string) $settings->get('teams_webhook_url', ''));

            if ($slackUrl === '' && $teamsUrl === '') {
                return;
            }

            SendAlarmChatPost::dispatch(
                $slackUrl !== '' ? $slackUrl : null,
                $teamsUrl !== '' ? $teamsUrl : null,
                $title,
                $body,
            );
        } catch (Throwable) {
            // best-effort — Chat-Posts dürfen den Alarm nie blockieren
        }
    }
}
