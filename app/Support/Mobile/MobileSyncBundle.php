<?php

namespace App\Support\Mobile;

use App\Enums\CrisisRole;
use App\Http\Controllers\Api\MobileSyncController;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Department;
use App\Models\EmergencyLevel;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\FallbackProcess;
use App\Models\HandbookVersion;
use App\Models\InsurancePolicy;
use App\Models\Location;
use App\Models\Role;
use App\Models\Scenario;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use App\Support\Incident\Cockpit;
use Illuminate\Support\Collection;

/**
 * Stellt das Offline-Datenpaket für die Notfall-App eines Mandanten zusammen
 * (siehe app/MOBILE-APP-BRIEF.md, Abschnitt 5/6).
 *
 * Liefert bewusst KEIN `synced_at`/`version` — diese (nicht inhaltlichen)
 * Felder ergänzt der {@see MobileSyncController}, der
 * über einen Fingerprint dieses Bundles den Delta-Sync steuert.
 *
 * WICHTIG (Mandanten-Isolation): Im Mobile-API-Kontext gibt es keinen
 * angemeldeten Web-Nutzer, wodurch der {@see CurrentCompanyScope}
 * NICHT greift. Jede Abfrage MUSS daher explizit auf `company_id` einschränken.
 * Krisenstab und Wiederanlauf werden aus {@see Cockpit} übernommen (dort bereits
 * durchgängig explizit gefiltert), damit die App dieselben Daten wie die
 * Notfallkarte zeigt.
 */
class MobileSyncBundle
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Company $company): array
    {
        $cockpit = Cockpit::for($company);
        $version = $company->currentHandbookVersion();

        return [
            'handbook' => self::handbook($company, $version),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'locations' => self::locations($company),
            'crisis_roles' => self::crisisRoles($cockpit->crisisStaff),
            'service_providers' => self::serviceProviders($company),
            'emergency_resources' => self::emergencyResources($company),
            'recovery_order' => self::recoveryOrder($cockpit->recoveryOrder),
            'scenarios' => self::scenarios($company),
            'aushang_codes' => self::aushangCodes($company),
        ];
    }

    /**
     * Handbuch-Eintrag: bevorzugt die freigegebene, revisionssichere PDF-Version;
     * sonst ein Live-Fallback auf das aktuelle Handbuch (Route-Marker `current`),
     * damit das vorhandene Handbuch auch ohne formale Freigabe mobil verfügbar ist.
     *
     * @return array<string, mixed>
     */
    private static function handbook(Company $company, ?HandbookVersion $version): array
    {
        if ($version !== null && $version->hasPdf()) {
            return [
                'version_id' => $version->id,
                'version' => $version->version,
                'hash' => $version->pdf_hash,
                'approved_at' => $version->approved_at?->toIso8601String(),
                'pdf_url' => route('api.mobile.handbook.pdf', ['version' => $version->id]),
            ];
        }

        return [
            'version_id' => 'current',
            'version' => $version?->version ?? 'aktuell',
            'hash' => self::liveHandbookSignature($company),
            'approved_at' => $version?->approved_at?->toIso8601String(),
            'pdf_url' => route('api.mobile.handbook.pdf', ['version' => 'current']),
        ];
    }

    /**
     * Günstiger Inhalts-Fingerprint des Live-Handbuchs (Count + max(updated_at)
     * über handbuchrelevante, firmengebundene Tabellen), damit die App das PDF
     * nur bei Änderungen neu lädt. Bewusst breit, aber nicht erschöpfend.
     */
    /**
     * Erhöhen, wenn sich das PDF-*Layout* (nicht die Daten) ändert — damit die
     * Apps das Live-Handbuch trotz unveränderter Inhalte neu laden.
     */
    private const HANDBOOK_RENDER_VERSION = 2;

    private static function liveHandbookSignature(Company $company): string
    {
        $models = [
            Location::class,
            Department::class,
            Employee::class,
            Role::class,
            System::class,
            Scenario::class,
            ServiceProvider::class,
            Contract::class,
            InsurancePolicy::class,
            EmergencyResource::class,
            EmergencyLevel::class,
            FallbackProcess::class,
        ];

        $parts = [
            'render:'.self::HANDBOOK_RENDER_VERSION,
            'company:'.$company->id.':'.($company->updated_at?->getTimestamp() ?? 0),
        ];

        foreach ($models as $model) {
            try {
                $row = $model::query()
                    ->withoutGlobalScope(CurrentCompanyScope::class)
                    ->where('company_id', $company->id)
                    ->selectRaw('COUNT(*) as c, MAX(updated_at) as u')
                    ->first();
                $parts[] = class_basename($model).':'.($row->c ?? 0).':'.($row->u ?? '');
            } catch (\Throwable) {
                // Modell ohne company_id/updated_at → überspringen.
            }
        }

        return 'live:'.substr(hash('sha256', implode('|', $parts)), 0, 32);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function locations(Company $company): array
    {
        return $company->locations()
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'is_headquarters' => (bool) $location->is_headquarters,
                'address' => trim(collect([
                    $location->street,
                    trim(($location->postal_code ?? '').' '.($location->city ?? '')),
                ])->filter()->implode(', ')),
                'phone' => $location->phone,
            ])
            ->all();
    }

    /**
     * Flacht Cockpit-Krisenstab (Hauptperson + Vertretungen je Rolle) auf eine
     * einfache Kontaktliste ab.
     *
     * @param  list<array{role: CrisisRole, role_label: string, main: ?Employee, deputies: Collection<int, Employee>}>  $crisisStaff
     * @return array<int, array<string, mixed>>
     */
    private static function crisisRoles(array $crisisStaff): array
    {
        $rows = [];

        foreach ($crisisStaff as $entry) {
            $role = $entry['role']->value;
            $label = $entry['role_label'];

            if ($entry['main'] instanceof Employee) {
                $rows[] = self::contactRow($role, $label, $entry['main'], false);
            }

            foreach ($entry['deputies'] as $deputy) {
                $rows[] = self::contactRow($role, $label, $deputy, true);
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private static function contactRow(string $role, string $label, Employee $employee, bool $isDeputy): array
    {
        return [
            'role' => $role,
            'role_label' => $label,
            'person' => trim($employee->first_name.' '.$employee->last_name),
            'phone' => $employee->mobile_phone ?: ($employee->work_phone ?: $employee->private_phone),
            'is_deputy' => $isDeputy,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function serviceProviders(Company $company): array
    {
        return ServiceProvider::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceProvider $provider) => [
                'id' => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type instanceof \BackedEnum ? $provider->type->value : (string) $provider->type,
                'contact_name' => $provider->contact_name,
                'emergency_phone' => $provider->hotline,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function emergencyResources(Company $company): array
    {
        return EmergencyResource::query()
            ->where('company_id', $company->id)
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->map(fn (EmergencyResource $resource) => [
                'id' => $resource->id,
                'name' => $resource->name,
                'detail' => $resource->description,
                'location' => $resource->location,
            ])
            ->all();
    }

    /**
     * @param  list<array{system: System, level_name: ?string, rto_minutes: ?int}>  $recoveryOrder
     * @return array<int, array<string, mixed>>
     */
    private static function recoveryOrder(array $recoveryOrder): array
    {
        $rows = [];
        $position = 1;

        foreach ($recoveryOrder as $item) {
            $system = $item['system'];
            $rows[] = [
                'position' => $position++,
                'system' => $system instanceof System ? $system->name : (string) $system,
                'rto_minutes' => $item['rto_minutes'],
                'level' => $item['level_name'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function scenarios(Company $company): array
    {
        return Scenario::query()
            ->where('company_id', $company->id)
            ->with(['steps' => fn ($q) => $q->orderBy('sort')])
            ->orderBy('name')
            ->get()
            ->map(fn (Scenario $scenario) => [
                'id' => $scenario->id,
                'title' => $scenario->name,
                'trigger' => $scenario->trigger,
                'steps' => $scenario->steps->map(fn ($step) => [
                    'position' => $step->sort,
                    'text' => trim($step->title.($step->description ? ' — '.$step->description : '')),
                    'role' => $step->responsible,
                ])->all(),
            ])
            ->all();
    }

    /**
     * Notfallaushang-Codes: pro Szenario einer. Szenarien sind mandantenweit
     * (nicht standortgebunden), daher `location_id` = null.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function aushangCodes(Company $company): array
    {
        return Scenario::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Scenario $scenario) => [
                'location_id' => null,
                'scenario_id' => $scenario->id,
                'label' => $scenario->name,
            ])
            ->all();
    }
}
