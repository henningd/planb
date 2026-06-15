<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\PreventiveMeasureCategory;
use App\Enums\PreventiveMeasureEffectiveness;
use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\PreventiveMeasureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vorbeugende Maßnahme, die das Ausfallrisiko eines Systems senkt.
 *
 * Zwei Naturen in einem Model: Ist ein `interval` gesetzt, handelt es sich um
 * eine wiederkehrende Kontrolle (mit `next_due_at`, Reminder und Inbox-Eintrag);
 * ohne Intervall ist es eine einmalige Maßnahme (Status geplant → aktiv).
 */
#[Fillable([
    'company_id',
    'system_id',
    'title',
    'description',
    'category',
    'status',
    'interval',
    'target_date',
    'last_executed_at',
    'next_due_at',
    'effectiveness',
    'responsible_employee_id',
    'responsible_role_id',
    'risk_id',
    'result_notes',
    'last_reminder_sent_at',
    'sort',
])]
class PreventiveMeasure extends Model
{
    /** @use HasFactory<PreventiveMeasureFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
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

    /**
     * @return BelongsTo<Risk, $this>
     */
    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function isRecurring(): bool
    {
        return $this->interval !== null;
    }

    public function isOverdue(): bool
    {
        if ($this->status === PreventiveMeasureStatus::Paused) {
            return false;
        }

        if ($this->next_due_at !== null) {
            return $this->next_due_at->isPast();
        }

        return $this->target_date !== null
            && $this->target_date->isPast()
            && in_array($this->status, [PreventiveMeasureStatus::Planned, PreventiveMeasureStatus::InProgress], true);
    }

    /**
     * Maßnahme als durchgeführt markieren: setzt das Ausführungsdatum, berechnet
     * bei wiederkehrenden Maßnahmen die nächste Fälligkeit und aktiviert sie.
     */
    public function markExecuted(?CarbonInterface $at = null): void
    {
        $at ??= CarbonImmutable::now();
        $this->last_executed_at = $at;
        $this->status = PreventiveMeasureStatus::Active;

        if ($this->interval !== null) {
            $this->next_due_at = $at->copy()->addMonths($this->interval->months());
        }

        $this->save();
    }

    /**
     * Wiederkehrende Maßnahmen mit gesetzter Fälligkeit – Grundlage für Inbox und Reminder.
     *
     * @param  Builder<PreventiveMeasure>  $query
     */
    public function scopeRecurringDue(Builder $query): void
    {
        $query->whereNotNull('interval')
            ->whereNotNull('next_due_at')
            ->where('status', '!=', PreventiveMeasureStatus::Paused->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PreventiveMeasureCategory::class,
            'status' => PreventiveMeasureStatus::class,
            'interval' => PreventiveMeasureInterval::class,
            'effectiveness' => PreventiveMeasureEffectiveness::class,
            'target_date' => 'date',
            'last_executed_at' => 'date',
            'next_due_at' => 'date',
            'last_reminder_sent_at' => 'datetime',
            'sort' => 'integer',
        ];
    }
}
