<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\ProcessCriticality;
use Database\Factories\BusinessProcessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Geschäftsprozess als Gegenstand der Business-Impact-Analyse (BIA): erfasst
 * Kritikalität, Wiederanlaufziele (MTPD/RTO/RPO) sowie die zur Durchführung
 * benötigten Systeme und Verantwortlichkeiten.
 */
#[Fillable([
    'company_id',
    'name',
    'description',
    'criticality',
    'mtpd_minutes',
    'rto_minutes',
    'rpo_minutes',
    'peak_times',
    'responsible_employee_id',
    'responsible_role_id',
    'notes',
    'sort',
])]
class BusinessProcess extends Model
{
    /** @use HasFactory<BusinessProcessFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name;
    }

    /**
     * Systeme, die für die Durchführung dieses Prozesses benötigt werden.
     *
     * @return BelongsToMany<System, $this>
     */
    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'business_process_system')
            ->withPivot('note')
            ->withTimestamps();
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criticality' => ProcessCriticality::class,
            'mtpd_minutes' => 'integer',
            'rto_minutes' => 'integer',
            'rpo_minutes' => 'integer',
            'sort' => 'integer',
        ];
    }
}
