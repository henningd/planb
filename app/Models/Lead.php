<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Enums\Nis2Readiness;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ein über den öffentlichen NIS2-Quick-Check eingesammelter Interessent.
 *
 * Bewusst nicht mandantengebunden ({@see BelongsToCurrentCompany}
 * fehlt absichtlich): Leads entstehen ohne Login vor jeglicher Firmenzuordnung.
 * Die E-Mail-Adresse wird erst nach Double-Opt-In-Bestätigung (`confirmed_at`)
 * für den Versand der detaillierten Auswertung genutzt.
 */
#[Fillable([
    'email',
    'company_name',
    'contact_name',
    'source',
    'answers',
    'score',
    'readiness',
    'consent_marketing',
    'consent_at',
    'ip_address',
    'user_agent',
    'confirmed_at',
    'report_sent_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'score' => 'integer',
            'readiness' => Nis2Readiness::class,
            'consent_marketing' => 'boolean',
            'consent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'report_sent_at' => 'datetime',
        ];
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * @param  Builder<Lead>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }
}
