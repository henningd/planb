<?php

namespace App\Models;

use Database\Factories\ScenarioRunStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'scenario_run_id',
    'sort',
    'title',
    'description',
    'responsible',
    'assigned_employee_id',
    'checked_at',
    'checked_by_user_id',
    'note',
])]
class ScenarioRunStep extends Model
{
    /** @use HasFactory<ScenarioRunStepFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<ScenarioRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ScenarioRun::class, 'scenario_run_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    /**
     * Für diesen Schritt zuständige/übernehmende Person (optional).
     *
     * @return BelongsTo<Employee, $this>
     */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function isChecked(): bool
    {
        return $this->checked_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }
}
