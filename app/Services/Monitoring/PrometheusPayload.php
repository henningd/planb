<?php

namespace App\Services\Monitoring;

/**
 * Normalisiert eine Prometheus-Alertmanager-Webhook-Payload in eine Liste von
 * NormalizedAlerts. Alertmanager bündelt mehrere Alerts in einer Nachricht.
 *
 * Erwartete Payload-Struktur (Alertmanager v4 Webhook):
 * {
 *   "alerts": [
 *     {
 *       "fingerprint": "abc123",
 *       "status": "firing" | "resolved",
 *       "labels": {"alertname": "...", "instance": "host:port", "severity": "critical"},
 *       "annotations": {"summary": "...", "description": "..."},
 *       "startsAt": "...", "endsAt": "..."
 *     }
 *   ]
 * }
 *
 * Idempotency-Key = fingerprint + startsAt + status. Der Fingerprint allein
 * identifiziert nur die Alert-IDENTITÄT (Label-Set) und bleibt über getrennte
 * Ausfälle hinweg gleich — erst startsAt unterscheidet die Episode. Ohne
 * startsAt würde ein erneuter Ausfall desselben Hosts als Duplikat verworfen.
 */
class PrometheusPayload
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<NormalizedAlert>
     */
    public static function normalize(array $payload): array
    {
        $alerts = $payload['alerts'] ?? [];
        if (! is_array($alerts)) {
            return [];
        }

        $result = [];
        foreach ($alerts as $alert) {
            if (! is_array($alert)) {
                continue;
            }
            $labels = is_array($alert['labels'] ?? null) ? $alert['labels'] : [];
            $annotations = is_array($alert['annotations'] ?? null) ? $alert['annotations'] : [];

            $instance = isset($labels['instance']) ? (string) $labels['instance'] : null;
            $host = $instance ? preg_replace('/:\d+$/', '', $instance) : null;
            if (($host === null || $host === '') && isset($labels['host'])) {
                $host = (string) $labels['host'];
            }

            $status = strtolower((string) ($alert['status'] ?? 'firing'));
            $normalizedStatus = $status === 'resolved' ? 'resolved' : 'firing';

            $fingerprint = (string) ($alert['fingerprint'] ?? '');
            $startsAt = trim((string) ($alert['startsAt'] ?? ''));
            $episode = $startsAt !== '' ? ':'.$startsAt : '';
            $idempotency = $fingerprint !== ''
                ? 'fp:'.$fingerprint.$episode.':'.$normalizedStatus
                : 'amgr:'.($labels['alertname'] ?? 'unknown').':'.($host ?? '').$episode.':'.$normalizedStatus;

            $subject = (string) ($annotations['summary']
                ?? $annotations['description']
                ?? $labels['alertname']
                ?? 'Prometheus alert');

            // Optionales Label `planb_system_id`: direkte, eindeutige Zuordnung
            // zu einem System — Vorrang vor dem Monitoring-Key-Matching.
            $systemId = isset($labels['planb_system_id']) ? trim((string) $labels['planb_system_id']) : '';

            $result[] = new NormalizedAlert(
                source: 'prometheus',
                idempotencyKey: $idempotency,
                status: $normalizedStatus,
                severity: isset($labels['severity']) ? strtolower((string) $labels['severity']) : null,
                host: $host,
                subject: $subject,
                rawPayload: $alert,
                systemId: $systemId !== '' ? $systemId : null,
            );
        }

        return $result;
    }
}
