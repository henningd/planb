<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\HandbookTestInterval;
use App\Enums\HandbookTestType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\HandbookTestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'type',
    'name',
    'description',
    'interval',
    'last_executed_at',
    'next_due_at',
    'responsible_employee_id',
    'responsible_role_id',
    'result_notes',
    'last_reminder_sent_at',
    'sort',
])]
class HandbookTest extends Model
{
    /** @use HasFactory<HandbookTestFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name ?? $this->type->label();
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    public function isOverdue(): bool
    {
        return $this->next_due_at !== null && $this->next_due_at->isPast();
    }

    public function markExecuted(?CarbonInterface $at = null): void
    {
        $at ??= CarbonImmutable::now();
        $this->last_executed_at = $at;

        $months = $this->interval->months();
        if ($months > 0) {
            $this->next_due_at = $at->copy()->addMonths($months);
        }

        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HandbookTestType::class,
            'interval' => HandbookTestInterval::class,
            'last_executed_at' => 'date',
            'next_due_at' => 'date',
            'last_reminder_sent_at' => 'datetime',
            'sort' => 'integer',
        ];
    }
}
