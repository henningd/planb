<?php

namespace App\Services\Monitoring;

use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Enums\ScenarioRunMode;
use App\Models\ApiToken;
use App\Models\IncidentReport;
use App\Models\MonitoringAlert;
use App\Models\Scenario;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use App\Support\Scenarios\StartScenarioRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertProcessor
{
    public function __construct(private readonly StartScenarioRun $startScenarioRun) {}

    /**
     * Severity-Werte (Zabbix + Prometheus zusammengezogen) ab denen ein
     * Incident automatisch angelegt wird. Alles darunter wird nur geloggt.
     *
     * @var list<string>
     */
    private const FIRING_THRESHOLD = ['high', 'disaster', 'critical', 'page'];

    /**
     * Verarbeitet einen einzelnen NormalizedAlert idempotent.
     *
     * Effekte:
     *  - Persistiert immer einen `MonitoringAlert`-Datensatz (zur Nachvollziehbarkeit).
     *  - Wenn `firing` + Severity über Schwelle + System gemappt → legt
     *    einen `IncidentReport` an (oder hängt sich an einen offenen für
     *    dasselbe System der letzten 24h an).
     *  - Wenn `resolved` → ergänzt eine Notiz am verlinkten Incident, aber
     *    schließt ihn NICHT (das tut ein Mensch).
     *  - Läuft für das System ein Wartungsfenster (`systems.monitoring_muted_until`
     *    in der Zukunft) → handling `muted`: Alert wird nur protokolliert,
     *    kein Incident, kein Auto-Alarm. Entwarnungen laufen normal weiter.
     */
    public function process(NormalizedAlert $alert, ApiToken $token): MonitoringAlert
    {
        $existing = MonitoringAlert::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $token->company_id)
            ->where('source', $alert->source)
            ->where('idempotency_key', $alert->idempotencyKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        $system = $this->matchSystem($alert, $token->company_id);

        [$record, $openedIncident] = DB::transaction(function () use ($alert, $token, $system) {
            $handling = $this->decideHandling($alert, $system);
            $incident = null;
            $note = null;
            $openedIncident = false;

            if ($handling === 'muted') {
                $note = sprintf(
                    'Wartungsfenster aktiv: Monitoring-Alarme sind bis %s pausiert — kein Incident, kein Auto-Alarm.',
                    $system->monitoring_muted_until->format('d.m.Y H:i'),
                );
            } elseif ($handling === 'created_incident') {
                [$incident, $openedIncident] = $this->openOrAttachIncident($alert, $system, $token->company_id);
            } elseif ($alert->isResolved() && $system) {
                $incident = $this->findRecentOpenIncident($system, $token->company_id);
                if ($incident) {
                    $handling = 'matched_existing';
                    $resolutionLine = sprintf(
                        '[%s] %s: RESOLVED – %s',
                        now()->format('Y-m-d H:i'),
                        $alert->source,
                        $alert->subject ?? $alert->host ?? 'system back online',
                    );
                    $incident->forceFill([
                        'notes' => trim((string) $incident->notes."\n".$resolutionLine),
                    ])->save();
                    $note = $resolutionLine;
                }
            }

            $record = MonitoringAlert::create([
                'company_id' => $token->company_id,
                'api_token_id' => $token->id,
                'system_id' => $system?->id,
                'incident_report_id' => $incident?->id,
                'source' => $alert->source,
                'idempotency_key' => $alert->idempotencyKey,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'host' => $alert->host,
                'subject' => $alert->subject,
                'payload' => $alert->rawPayload,
                'handling' => $handling,
                'note' => $note,
                'received_at' => now(),
            ]);

            return [$record, $openedIncident];
        });

        // Automatische Alarmierung (Opt-in je System): nur der ERSTE Alert,
        // der den Incident eröffnet, darf alarmieren — angehängte Folge-Alerts
        // lösen keinen weiteren Alarm aus (Schutz gegen Alarm-Stürme).
        if ($openedIncident && $system !== null && $system->emergency_scenario_id !== null) {
            $this->autoAlarm($record, $system);
        }

        return $record;
    }

    /**
     * Startet den mit dem System verknüpften Notfall als echten Alarm
     * (mode=real) über die bestehende StartScenarioRun-Action — damit greifen
     * Push, Quittierung und Eskalation automatisch. Läuft für das Szenario
     * bereits ein offener Run, wird nicht erneut alarmiert. Fehler dürfen die
     * Webhook-Verarbeitung nie abbrechen.
     */
    private function autoAlarm(MonitoringAlert $record, System $system): void
    {
        $scenario = Scenario::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $system->company_id)
            ->find($system->emergency_scenario_id);

        if ($scenario === null) {
            return;
        }

        $hasOpenRun = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $system->company_id)
            ->where('scenario_id', $scenario->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->exists();

        if ($hasOpenRun) {
            $this->appendNote($record, sprintf(
                'Automatische Alarmierung übersprungen: für Szenario „%s" läuft bereits ein offener Alarm.',
                $scenario->name,
            ));

            return;
        }

        try {
            $run = $this->startScenarioRun->handle(
                scenario: $scenario,
                startedByUserId: null,
                mode: ScenarioRunMode::Real,
                title: null,
                source: 'monitoring',
            );
        } catch (Throwable $e) {
            Log::warning('Automatische Alarmierung aus Monitoring-Alert fehlgeschlagen: '.$e->getMessage());

            return;
        }

        $this->appendNote($record, sprintf(
            'Automatische Alarmierung: Szenario „%s" als Ernstfall gestartet (Run %s).',
            $scenario->name,
            $run->id,
        ));
    }

    private function appendNote(MonitoringAlert $record, string $line): void
    {
        $record->forceFill([
            'note' => trim((string) $record->note."\n".$line),
        ])->save();
    }

    private function decideHandling(NormalizedAlert $alert, ?System $system): string
    {
        if (! $alert->isFiring()) {
            return 'ignored';
        }
        if (! $system) {
            return 'no_system_match';
        }
        if ($alert->severity !== null && ! in_array($alert->severity, self::FIRING_THRESHOLD, true)) {
            return 'severity_below_threshold';
        }
        // Wartungsfenster: kritischer Alert wird nur protokolliert — kein
        // Incident, kein Auto-Alarm. Entwarnungen (resolved) laufen normal,
        // da decideHandling nur für firing-Alerts hierher kommt.
        if ($system->isMonitoringMuted()) {
            return 'muted';
        }

        return 'created_incident';
    }

    private function matchSystem(NormalizedAlert $alert, string $companyId): ?System
    {
        // Explizite System-ID (Prometheus-Label `planb_system_id` / Zabbix-Feld
        // `system_id`) hat Vorrang — streng auf die Firma des Tokens begrenzt.
        // Unbekannte oder fremde IDs fallen auf das Key-Matching zurück.
        if ($alert->systemId !== null) {
            $system = System::query()
                ->withoutGlobalScope(CurrentCompanyScope::class)
                ->where('company_id', $companyId)
                ->whereKey($alert->systemId)
                ->first();

            if ($system !== null) {
                return $system;
            }
        }

        $candidates = array_filter([$alert->host, $alert->subject]);
        if ($candidates === []) {
            return null;
        }

        $systems = System::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $companyId)
            ->whereNotNull('monitoring_keys')
            ->get();

        foreach ($systems as $system) {
            $keys = is_array($system->monitoring_keys) ? $system->monitoring_keys : [];
            foreach ($keys as $key) {
                $needle = mb_strtolower((string) $key);
                if ($needle === '') {
                    continue;
                }
                foreach ($candidates as $candidate) {
                    if (str_contains(mb_strtolower((string) $candidate), $needle)) {
                        return $system;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: IncidentReport, 1: bool} Incident + true, wenn er von diesem Alert NEU eröffnet wurde
     */
    private function openOrAttachIncident(NormalizedAlert $alert, System $system, string $companyId): array
    {
        $existing = $this->findRecentOpenIncident($system, $companyId);
        if ($existing) {
            $existing->forceFill([
                'notes' => trim((string) $existing->notes."\n".sprintf(
                    '[%s] %s · %s · %s',
                    now()->format('Y-m-d H:i'),
                    $alert->source,
                    strtoupper((string) $alert->severity ?: 'firing'),
                    $alert->subject ?? $alert->host ?? '',
                )),
            ])->save();

            return [$existing, false];
        }

        $title = $alert->subject !== null && $alert->subject !== ''
            ? sprintf('[Auto · %s] %s', $alert->source, $alert->subject)
            : sprintf('[Auto · %s] %s', $alert->source, $system->name);

        $report = new IncidentReport;
        $report->forceFill([
            'company_id' => $companyId,
            'title' => mb_substr($title, 0, 250),
            'type' => IncidentType::Outage->value,
            'occurred_at' => now(),
            'notes' => sprintf(
                "Automatisch eröffnet aus Monitoring-Alert.\nQuelle: %s\nHost: %s\nSeverity: %s",
                $alert->source,
                $alert->host ?? '—',
                $alert->severity ?? '—',
            ),
        ])->save();

        foreach (ReportingObligation::applicableFor(IncidentType::Outage->value) as $obligation) {
            $report->obligations()->create(['obligation' => $obligation->value]);
        }

        return [$report, true];
    }

    private function findRecentOpenIncident(System $system, string $companyId): ?IncidentReport
    {
        $reportId = MonitoringAlert::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $companyId)
            ->where('system_id', $system->id)
            ->whereNotNull('incident_report_id')
            ->where('received_at', '>=', now()->subHours(24))
            ->orderByDesc('received_at')
            ->value('incident_report_id');

        if ($reportId === null) {
            return null;
        }

        return IncidentReport::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->find($reportId);
    }
}
