<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id',
    'current_step',
    'completed_steps',
    'skipped_steps',
    'paused_at',
    'completed_at',
    'dismissed_at',
])]
class OnboardingState extends Model
{
    use BelongsToCurrentCompany, HasUuids;

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null && $this->completed_at === null;
    }

    public function isDismissed(): bool
    {
        return $this->dismissed_at !== null && $this->completed_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'skipped_steps' => 'array',
            'paused_at' => 'datetime',
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }
}
