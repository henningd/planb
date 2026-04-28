<?php

namespace App\Models;

use App\Enums\RiskMitigationStatus;
use Database\Factories\RiskMitigationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'risk_id',
    'title',
    'description',
    'status',
    'target_date',
    'implemented_at',
    'responsible_employee_id',
    'system_task_id',
])]
class RiskMitigation extends Model
{
    /** @use HasFactory<RiskMitigationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Risk, $this>
     */
    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * @return BelongsTo<SystemTask, $this>
     */
    public function systemTask(): BelongsTo
    {
        return $this->belongsTo(SystemTask::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RiskMitigationStatus::class,
            'target_date' => 'date',
            'implemented_at' => 'date',
        ];
    }
}
