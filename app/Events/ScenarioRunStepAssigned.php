<?php

namespace App\Events;

use App\Models\ScenarioRunStep;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScenarioRunStepAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ScenarioRunStep $step,
        public ?string $assignedName,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('scenario-run.'.$this->step->scenario_run_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'step.assigned';
    }

    /**
     * @return array{step_id: string, assigned_name: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'step_id' => $this->step->id,
            'assigned_name' => $this->assignedName,
        ];
    }
}
