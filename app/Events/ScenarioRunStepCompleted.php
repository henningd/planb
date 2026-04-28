<?php

namespace App\Events;

use App\Models\ScenarioRunStep;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScenarioRunStepCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ScenarioRunStep $step,
        public string $userName,
        public ?string $completedAt,
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
        return 'step.completed';
    }

    /**
     * @return array{step_id: string, user_name: string, completed_at: ?string}
     */
    public function broadcastWith(): array
    {
        return [
            'step_id' => $this->step->id,
            'user_name' => $this->userName,
            'completed_at' => $this->completedAt,
        ];
    }
}
