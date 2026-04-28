<?php

namespace App\Services\Monitoring;

use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Models\ApiToken;
use App\Models\IncidentReport;
use App\Models\MonitoringAlert;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\DB;

class AlertProcessor
{
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

        return DB::transaction(function () use ($alert, $token, $system) {
            $handling = $this->decideHandling($alert, $system);
            $incident = null;
            $note = null;

            if ($handling === 'created_incident') {
                $incident = $this->openOrAttachIncident($alert, $system, $token->company_id);
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

            return MonitoringAlert::create([
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
        });
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

        return 'created_incident';
    }

    private function matchSystem(NormalizedAlert $alert, string $companyId): ?System
    {
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

    private function openOrAttachIncident(NormalizedAlert $alert, System $system, string $companyId): IncidentReport
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

            return $existing;
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

        return $report;
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
