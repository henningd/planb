<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ein Notfall (Szenario-Ablauf) wurde beendet oder abgebrochen — firmenweit
 * gebroadcastet, damit das Web-Dashboard eine Benachrichtigung zeigen kann
 * (Gegenstück zu {@see IncidentStarted}). Queued, damit ein nicht erreichbarer
 * Broadcast-Server das Beenden nie blockiert.
 */
class IncidentEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $companyId,
        public string $runId,
        public string $title,
        public string $outcome,
        public ?string $endedBy,
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
        return 'incident.ended';
    }

    /**
     * @return array{run_id: string, scenario_title: string, outcome: string, ended_by: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->runId,
            'scenario_title' => $this->title,
            'outcome' => $this->outcome,
            'ended_by' => $this->endedBy,
        ];
    }
}
