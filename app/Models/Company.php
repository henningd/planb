<?php

namespace App\Models;

use App\Enums\CrisisRole;
use App\Enums\Industry;
use App\Enums\KritisRelevance;
use App\Enums\LegalForm;
use App\Enums\Nis2Classification;
use App\Observers\CompanyObserver;
use Carbon\CarbonInterface;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'team_id',
    'name',
    'display_name',
    'logo_path',
    'primary_color',
    'industry',
    'legal_form',
    'kritis_relevant',
    'nis2_classification',
    'valid_from',
    'cyber_insurance_deductible',
    'budget_it_lead',
    'budget_emergency_officer',
    'budget_management',
    'data_protection_authority_name',
    'data_protection_authority_phone',
    'data_protection_authority_website',
    'employee_count',
    'locations_count',
    'review_cycle_months',
    'last_reviewed_at',
    'last_reminder_sent_at',
])]
#[ObservedBy([CompanyObserver::class])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class)->orderBy('last_name')->orderBy('first_name');
    }

    /**
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<EmergencyLevel, $this>
     */
    public function emergencyLevels(): HasMany
    {
        return $this->hasMany(EmergencyLevel::class)->orderBy('sort');
    }

    /**
     * @return HasMany<System, $this>
     */
    public function systems(): HasMany
    {
        return $this->hasMany(System::class);
    }

    /**
     * @return HasMany<SystemPriority, $this>
     */
    public function systemPriorities(): HasMany
    {
        return $this->hasMany(SystemPriority::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Scenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class)->orderBy('name');
    }

    /**
     * @return HasMany<ScenarioRun, $this>
     */
    public function scenarioRuns(): HasMany
    {
        return $this->hasMany(ScenarioRun::class);
    }

    /**
     * @return HasMany<IncidentReport, $this>
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }

    /**
     * @return HasMany<CommunicationTemplate, $this>
     */
    public function communicationTemplates(): HasMany
    {
        return $this->hasMany(CommunicationTemplate::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<InsurancePolicy, $this>
     */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class)->orderBy('type')->orderBy('insurer');
    }

    /**
     * @return HasMany<HandbookShare, $this>
     */
    public function handbookShares(): HasMany
    {
        return $this->hasMany(HandbookShare::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<EmergencyResource, $this>
     */
    public function emergencyResources(): HasMany
    {
        return $this->hasMany(EmergencyResource::class)->orderBy('type')->orderBy('sort');
    }

    /**
     * @return HasMany<HandbookVersion, $this>
     */
    public function handbookVersions(): HasMany
    {
        return $this->hasMany(HandbookVersion::class)->orderByDesc('changed_at');
    }

    /**
     * @return HasMany<HandbookTest, $this>
     */
    public function handbookTests(): HasMany
    {
        return $this->hasMany(HandbookTest::class)->orderBy('next_due_at');
    }

    public function currentHandbookVersion(): ?HandbookVersion
    {
        return $this->handbookVersions()
            ->whereNotNull('approved_at')
            ->orderByDesc('approved_at')
            ->orderByDesc('changed_at')
            ->first();
    }

    public function crisisRoleHolder(CrisisRole $role, bool $deputy = false): ?Employee
    {
        return Employee::query()
            ->where('company_id', $this->id)
            ->where('crisis_role', $role->value)
            ->where('is_crisis_deputy', $deputy)
            ->first();
    }

    /**
     * Hauptansprechperson der Firma: Mitarbeiter mit Krisenrolle „Geschäftsführung"
     * (Hauptperson, nicht Vertretung). Fällt zurück auf irgendeinen Geschäftsführungs-
     * Mitarbeiter, wenn keine Hauptperson markiert ist.
     */
    public function primaryContact(): ?Employee
    {
        return $this->crisisRoleHolder(CrisisRole::Management)
            ?? Employee::query()
                ->where('company_id', $this->id)
                ->where('crisis_role', CrisisRole::Management->value)
                ->first();
    }

    public function hasPrimaryContact(): bool
    {
        return $this->primaryContact() !== null;
    }

    /**
     * Date when the next review is due. Null if no confirmation has happened
     * yet – in that case the company is "due" once it is older than the cycle.
     */
    public function reviewDueAt(): ?CarbonInterface
    {
        if ($this->last_reviewed_at) {
            return $this->last_reviewed_at->copy()->addMonths($this->review_cycle_months);
        }

        return $this->created_at?->copy()->addMonths($this->review_cycle_months);
    }

    public function isReviewDue(): bool
    {
        $due = $this->reviewDueAt();

        return $due !== null && $due->isPast();
    }

    /**
     * Anzeige-Name des Mandanten — fällt auf den Firmennamen zurück.
     */
    public function brandName(): string
    {
        return trim((string) $this->display_name) !== ''
            ? (string) $this->display_name
            : (string) $this->name;
    }

    /**
     * Öffentlich aufrufbare URL des hochgeladenen Logos oder null.
     */
    public function logoUrl(): ?string
    {
        if ($this->logo_path === null || $this->logo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Primärfarbe als Hex (#rrggbb), Fallback ist die Plattform-Default-Farbe.
     */
    public function brandColor(): string
    {
        $color = (string) $this->primary_color;
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1) {
            return $color;
        }

        return '#4f46e5';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'industry' => Industry::class,
            'legal_form' => LegalForm::class,
            'kritis_relevant' => KritisRelevance::class,
            'nis2_classification' => Nis2Classification::class,
            'valid_from' => 'date',
            'budget_it_lead' => 'decimal:2',
            'budget_emergency_officer' => 'decimal:2',
            'budget_management' => 'decimal:2',
            'employee_count' => 'integer',
            'locations_count' => 'integer',
            'review_cycle_months' => 'integer',
            'last_reviewed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
        ];
    }
}
