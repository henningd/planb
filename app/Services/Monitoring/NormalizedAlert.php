<?php

namespace App\Services\Monitoring;

/**
 * Quellen-unabhängige Repräsentation eines Monitoring-Alarms, wie ihn der
 * AlertProcessor verarbeitet. Adapter (Zabbix, Prometheus) liefern eine
 * Liste solcher Objekte aus ihrer jeweiligen Payload.
 *
 * @phpstan-type AlertStatus 'firing'|'resolved'
 */
class NormalizedAlert
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public readonly string $source,
        public readonly string $idempotencyKey,
        public readonly string $status,
        public readonly ?string $severity,
        public readonly ?string $host,
        public readonly ?string $subject,
        public readonly array $rawPayload,
    ) {}

    public function isFiring(): bool
    {
        return $this->status === 'firing';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
