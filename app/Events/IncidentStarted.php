<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ein Notfall (Szenario-Ablauf) wurde ausgelöst — firmenweit gebroadcastet,
 * damit das Web-Dashboard auf jeder Seite eine Benachrichtigung zeigen kann
 * (analog zur Push-Alarmierung der Apps). Queued ({@see ShouldBroadcast}), damit
 * ein nicht erreichbarer Broadcast-Server das Auslösen nie blockiert.
 */
class IncidentStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $companyId,
        public string $runId,
        public string $scenarioId,
        public string $scenarioTitle,
        public ?string $startedBy,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('company.'.$this->companyId)];
    }

    public function broadcastAs(): string
    {
        return 'incident.started';
    }

    /**
     * @return array{run_id: string, scenario_id: string, scenario_title: string, started_by: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->runId,
            'scenario_id' => $this->scenarioId,
            'scenario_title' => $this->scenarioTitle,
            'started_by' => $this->startedBy,
        ];
    }
}
