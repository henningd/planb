<?php

namespace App\Events;

use App\Models\ScenarioRunMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScenarioRunMessagePosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ScenarioRunMessage $message,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('scenario-run.'.$this->message->scenario_run_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }

    /**
     * @return array{id: string, author: string, body: string, at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'author' => $this->message->author_name ?? $this->message->user?->name ?? 'System',
            'body' => $this->message->body,
            'at' => $this->message->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
