<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'company_id',
    'system_id',
    'title',
    'description',
    'due_date',
    'completed_at',
    'sort',
])]
class SystemTask extends Model
{
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'system_task_employee')
            ->withPivot('raci_role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<ServiceProvider, $this>
     */
    public function providerAssignees(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_system_task')
            ->withPivot('raci_role')
            ->withTimestamps();
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone()
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function auditLabel(): string
    {
        return $this->title;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
