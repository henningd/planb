<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Database\Factories\FallbackProcessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'title',
    'description',
    'trigger',
    'responsible_role_id',
    'responsible_employee_id',
    'max_duration_hours',
    'handover_notes',
    'priority',
    'notes',
    'sort',
])]
class FallbackProcess extends Model
{
    /** @use HasFactory<FallbackProcessFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class)
            ->withPivot(['note', 'sort'])
            ->withTimestamps()
            ->orderBy('fallback_process_system.sort');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_duration_hours' => 'integer',
            'priority' => 'integer',
            'sort' => 'integer',
        ];
    }
}
