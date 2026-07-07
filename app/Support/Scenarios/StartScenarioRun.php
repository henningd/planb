<?php

namespace App\Support\Scenarios;

use App\Enums\ScenarioRunMode;
use App\Events\IncidentStarted;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Services\Chat\AlarmChatNotifier;
use App\Support\Push\PushNotifier;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Startet einen Szenario-Ablauf (Notfall) und kopiert die Szenario-Schritte in
 * eine frische Bearbeitungsliste. Gemeinsame Logik für das Web-Frontend
 * (Incident-Launcher) und die Notfall-App (Mobile-API), damit ein per App
 * ausgelöster Notfall exakt so aussieht wie ein im Dashboard gestarteter.
 *
 * Bei einem echten Notfall (Modus {@see ScenarioRunMode::Real}) werden zusätzlich
 * alle registrierten Geräte der Firma per Push alarmiert.
 */
class StartScenarioRun
{
    public function __construct(
        private readonly PushNotifier $push,
        private readonly AlarmChatNotifier $chat,
    ) {}

    /**
     * @param  int|null  $startedByUserId  NULL bei automatischer Auslösung (z. B. Monitoring-Alert)
     * @param  ScenarioRunMode|string  $mode  Enum oder dessen Wert ('real'/'drill')
     */
    public function handle(
        Scenario $scenario,
        ?int $startedByUserId,
        ScenarioRunMode|string $mode = ScenarioRunMode::Real,
        ?string $title = null,
        string $source = 'web',
        ?string $triggerDetail = null,
    ): ScenarioRun {
        $mode = $mode instanceof ScenarioRunMode ? $mode : ScenarioRunMode::from($mode);
        $scenario->loadMissing('steps');

        $title = filled($title)
            ? $title
            : $scenario->name.' · '.now()->format('d.m.Y H:i');

        $run = DB::transaction(function () use ($scenario, $startedByUserId, $mode, $title, $source, $triggerDetail) {
            $run = ScenarioRun::create([
                'company_id' => $scenario->company_id,
                'scenario_id' => $scenario->id,
                'started_by_user_id' => $startedByUserId,
                'title' => $title,
                'mode' => $mode->value,
                'source' => $source,
                'trigger_detail' => $triggerDetail,
                'started_at' => now(),
            ]);

            foreach ($scenario->steps as $step) {
                $run->steps()->create([
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                ]);
            }

            return $run;
        });

        // Krisen-Logbuch: Auslösung revisionssicher festhalten (mit Quelle App/Web).
        CrisisLogEntry::create([
            'company_id' => $run->company_id,
            'scenario_run_id' => $run->id,
            'user_id' => $startedByUserId,
            'type' => 'system',
            'source' => $source,
            'message' => 'Notfall ausgelöst: '.$scenario->name,
            'occurred_at' => now(),
        ]);

        $this->alarm($scenario, $run, $startedByUserId);

        return $run;
    }

    /**
     * Alarmierung darf das Auslösen nie blockieren – Fehler werden geschluckt.
     * Drei Kanäle: Push an die Geräte (Apps), Karte in die konfigurierten
     * Slack-/Teams-Kanäle ({@see AlarmChatNotifier}) und ein firmenweiter
     * Broadcast fürs Web-Dashboard ({@see IncidentStarted}). Übungen (API v1.1) alarmieren die
     * Geräte ebenfalls — sichtbar mit Präfix „ÜBUNG: " und Data-Key `is_drill=1`;
     * nur der Web-Broadcast (Dashboard-Banner) bleibt Ernstfällen vorbehalten.
     */
    private function alarm(Scenario $scenario, ScenarioRun $run, ?int $startedByUserId): void
    {
        $isDrill = $run->isDrill();

        $startedBy = $startedByUserId !== null
            ? User::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($startedByUserId)?->name
            : null;

        // Automatische Auslösung sichtbar machen: Feed und Chat-Karte nennen
        // das IT-Monitoring (inkl. Host) statt gar keinen Auslöser.
        if ($startedBy === null && $run->source === 'monitoring') {
            $startedBy = trim('IT-Monitoring'.($run->trigger_detail ? ' · '.$run->trigger_detail : ''));
        }

        // Kein „ÜBUNG: "-Präfix im Feed-Titel: die Apps rendern bei is_drill ein
        // eigenes Badge — das Präfix bleibt den System-Pushes vorbehalten.
        AppNotification::create([
            'company_id' => $scenario->company_id,
            'type' => 'incident_started',
            'title' => 'Notfall gemeldet',
            'body' => $scenario->name,
            'triggered_by_name' => $startedBy,
            'severity' => 'critical',
            'scenario_run_id' => $run->id,
        ]);

        try {
            $company = Company::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->find($scenario->company_id);

            if ($company !== null) {
                $this->push->incident($company, $scenario->id, $scenario->name, $startedByUserId, $isDrill);
                $this->chat->incidentStarted($company, $scenario->name, $startedBy, $isDrill);
            }
        } catch (Throwable) {
            // best-effort
        }

        if ($isDrill) {
            return;
        }

        try {
            event(new IncidentStarted(
                companyId: $scenario->company_id,
                runId: $run->id,
                scenarioId: $scenario->id,
                scenarioTitle: $scenario->name,
                startedBy: $startedBy,
            ));
        } catch (Throwable) {
            // best-effort; Broadcast darf das Auslösen nie blockieren
        }
    }
}
