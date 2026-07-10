<?php

namespace App\Models;

use App\Concerns\BelongsToCurrentCompany;
use App\Concerns\LogsAudit;
use App\Enums\AuthorityContactType;
use App\Enums\Industry;
use App\Observers\CompanyObserver;
use Database\Factories\AuthorityContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Behörde, Meldestelle oder externe Einrichtung, die im Ernst-/Meldefall zu
 * kontaktieren ist (Datenschutzaufsicht, BSI, Polizei/Cybercrime, Feuerwehr,
 * Gesundheitsamt, Berufsgenossenschaft, Kreisleitstelle …). Firmen-pflegbar und
 * mit einer passenden Kommunikationsvorlage verknüpfbar.
 */
#[Fillable([
    'company_id',
    'type',
    'name',
    'occasion',
    'deadline',
    'phone',
    'email',
    'contact_way',
    'address',
    'contact_name',
    'responsible_role_id',
    'communication_template_id',
    'notes',
    'sort',
])]
class AuthorityContact extends Model
{
    /** @use HasFactory<AuthorityContactFactory> */
    use BelongsToCurrentCompany, HasFactory, HasUuids, LogsAudit;

    public function auditLabel(): string
    {
        return $this->name.' ('.$this->type->label().')';
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function responsibleRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'responsible_role_id');
    }

    /**
     * Passende Kommunikationsvorlage (z. B. „Meldung Datenschutzaufsicht").
     *
     * @return BelongsTo<CommunicationTemplate, $this>
     */
    public function communicationTemplate(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AuthorityContactType::class,
        ];
    }

    /**
     * Branchenspezifische Standard-Startliste, die der {@see CompanyObserver}
     * bei Firmengründung als bearbeitbare Vorlage anlegt. Allgemeine Stellen für
     * jede Firma, plus branchenspezifische Ergänzungen. Konkrete Ansprechpartner/
     * Nummern trägt der Mandant selbst nach.
     *
     * @return array<int, array{type: AuthorityContactType, name: string, occasion: string, deadline: string}>
     */
    public static function defaultsForIndustry(?Industry $industry): array
    {
        $base = [
            [
                'type' => AuthorityContactType::DataProtection,
                'name' => 'Zuständige Datenschutzaufsichtsbehörde',
                'occasion' => 'Meldepflichtige Verletzung des Schutzes personenbezogener Daten (Art. 33 DSGVO)',
                'deadline' => 'binnen 72 Stunden',
            ],
            [
                'type' => AuthorityContactType::Bsi,
                'name' => 'BSI-Meldestelle (falls NIS2-/KRITIS-pflichtig)',
                'occasion' => 'Erheblicher Sicherheitsvorfall (NIS2 / BSIG)',
                'deadline' => 'Erstmeldung unverzüglich, spätestens 24 h',
            ],
            [
                'type' => AuthorityContactType::Police,
                'name' => 'Polizei / Zentrale Ansprechstelle Cybercrime (ZAC)',
                'occasion' => 'Straftat, Cyberangriff, Erpressung',
                'deadline' => 'unverzüglich',
            ],
            [
                'type' => AuthorityContactType::FireRescue,
                'name' => 'Feuerwehr / Rettungsdienst',
                'occasion' => 'Brand, Personenschaden, akute Gefahrenlage',
                'deadline' => 'sofort (112)',
            ],
            [
                'type' => AuthorityContactType::EmployersLiability,
                'name' => 'Zuständige Berufsgenossenschaft',
                'occasion' => 'Meldepflichtiger Arbeitsunfall',
                'deadline' => 'binnen 3 Tagen (schwere Fälle sofort)',
            ],
            [
                'type' => AuthorityContactType::Legal,
                'name' => 'Rechtsberatung / Datenschutzbeauftragte(r)',
                'occasion' => 'Rechtliche Bewertung von Vorfall und Meldepflichten',
                'deadline' => 'nach Bedarf',
            ],
        ];

        $byIndustry = match ($industry) {
            Industry::Produktion => [
                ['type' => AuthorityContactType::OccupationalSafety, 'name' => 'Arbeitsschutzbehörde / Gewerbeaufsicht', 'occasion' => 'Schwerer Arbeitsunfall, Gefahrstofffreisetzung', 'deadline' => 'unverzüglich'],
                ['type' => AuthorityContactType::Environment, 'name' => 'Umweltbehörde', 'occasion' => 'Umweltrelevanter Störfall / Austritt', 'deadline' => 'unverzüglich'],
                ['type' => AuthorityContactType::Water, 'name' => 'Wasserbehörde', 'occasion' => 'Gewässerverunreinigung', 'deadline' => 'unverzüglich'],
            ],
            Industry::OeffentlicheEinrichtung => [
                ['type' => AuthorityContactType::DispatchCenter, 'name' => 'Kreisleitstelle', 'occasion' => 'Großschadenslage, Ausfall kritischer Dienste', 'deadline' => 'sofort'],
                ['type' => AuthorityContactType::DisasterControl, 'name' => 'Katastrophenschutz / untere Katastrophenschutzbehörde', 'occasion' => 'Katastrophenfall', 'deadline' => 'sofort'],
                ['type' => AuthorityContactType::MunicipalIt, 'name' => 'Kommunaler IT-Dienstleister / CERT', 'occasion' => 'IT-Sicherheitsvorfall', 'deadline' => 'unverzüglich'],
                ['type' => AuthorityContactType::SupervisoryAuthority, 'name' => 'Kommunalaufsicht / Aufsichtsbehörde', 'occasion' => 'Meldepflichtige Vorkommnisse', 'deadline' => 'nach Vorgabe'],
            ],
            Industry::Handel, Industry::Handwerk, Industry::Dienstleistung => [
                ['type' => AuthorityContactType::TradeSupervision, 'name' => 'Gewerbeaufsicht', 'occasion' => 'Arbeitsschutz-/Betriebsauflagen', 'deadline' => 'nach Vorgabe'],
            ],
            default => [],
        };

        return array_merge($base, $byIndustry);
    }
}
