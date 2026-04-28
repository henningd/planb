<?php

namespace App\Services\Monitoring;

/**
 * Normalisiert einen Zabbix-Webhook-Aufruf in ein NormalizedAlert.
 *
 * Erwartete Payload-Struktur (per Action-Operation in Zabbix konfigurierbar):
 * {
 *   "host": "srv-prod-01",
 *   "trigger_id": "12345",
 *   "event_id": "9876",
 *   "severity": "high",   // optional: information|warning|average|high|disaster
 *   "status": "PROBLEM",  // PROBLEM | RESOLVED | OK
 *   "subject": "Disk space low on /var",
 *   "message": "..."
 * }
 *
 * Idempotency-Key = event_id (falls vorhanden) oder host + trigger_id.
 */
class ZabbixPayload
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<NormalizedAlert>
     */
    public static function normalize(array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? 'firing'));
        $normalizedStatus = in_array($status, ['resolved', 'ok', 'recovered'], true) ? 'resolved' : 'firing';

        $eventId = (string) ($payload['event_id'] ?? '');
        $triggerId = (string) ($payload['trigger_id'] ?? '');
        $host = isset($payload['host']) ? (string) $payload['host'] : null;

        $idempotency = $eventId !== ''
            ? 'evt:'.$eventId
            : 'trg:'.$triggerId.':'.($host ?? '').':'.$normalizedStatus;

        return [new NormalizedAlert(
            source: 'zabbix',
            idempotencyKey: $idempotency,
            status: $normalizedStatus,
            severity: isset($payload['severity']) ? strtolower((string) $payload['severity']) : null,
            host: $host,
            subject: isset($payload['subject']) ? (string) $payload['subject'] : null,
            rawPayload: $payload,
        )];
    }
}
