<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\SystemCategory;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['company_id', 'name', 'description', 'category', 'system_priority_id', 'rto_minutes', 'rpo_minutes', 'downtime_cost_per_hour'])]
class System extends Model
{
    /** @use HasFactory<SystemFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    /**
     * @return BelongsTo<SystemPriority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(SystemPriority::class, 'system_priority_id');
    }

    /**
     * @return BelongsToMany<ServiceProvider, $this>
     */
    public function serviceProviders(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class)
            ->withPivot(['role', 'sort', 'note'])
            ->withTimestamps()
            ->orderBy('service_provider_system.sort');
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withPivot(['sort', 'note'])
            ->withTimestamps()
            ->orderBy('employee_system.sort');
    }

    /**
     * Systems this one depends on and that must come up first.
     *
     * @return BelongsToMany<System, $this>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            System::class,
            'system_dependencies',
            'system_id',
            'depends_on_system_id',
        )
            ->withPivot(['sort', 'note'])
            ->withTimestamps()
            ->orderBy('system_dependencies.sort');
    }

    /**
     * Systems that depend on this one (inverse).
     *
     * @return BelongsToMany<System, $this>
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            System::class,
            'system_dependencies',
            'depends_on_system_id',
            'system_id',
        )
            ->withPivot(['sort', 'note'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SystemCategory::class,
            'rto_minutes' => 'integer',
            'rpo_minutes' => 'integer',
            'downtime_cost_per_hour' => 'integer',
        ];
    }
}
