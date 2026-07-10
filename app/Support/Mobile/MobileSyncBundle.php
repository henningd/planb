<?php

namespace App\Support\Mobile;

use App\Enums\CrisisRole;
use App\Enums\ScenarioRunMode;
use App\Http\Controllers\Api\MobileSyncController;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CrisisLogEntry;
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
use App\Models\ScenarioRun;
use App\Models\ScenarioRunAcknowledgement;
use App\Models\ScenarioRunStep;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemTask;
use App\Scopes\CurrentCompanyScope;
use App\Support\Graph\RecoveryTimelineBuilder;
use App\Support\Incident\Cockpit;
use App\Support\RecoveryOrder;
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
            'recovery_order' => self::recoveryOrder($company, $cockpit->recoveryOrder),
            'scenarios' => self::scenarios($company),
            'active_runs' => self::activeRuns($company),
            'notifications' => self::notifications($company),
            'aushang_codes' => self::aushangCodes($company),
        ];
    }

    /**
     * Firmenweiter Benachrichtigungs-Feed (jüngste zuerst, begrenzt) für den
     * „Benachrichtigungen"-Verlauf in der App. Der Gelesen-Status wird lokal
     * je Gerät geführt, nicht hier.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function notifications(Company $company): array
    {
        $notifications = AppNotification::query()
            ->where('company_id', $company->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Übungs-Flag je referenziertem Run auflösen (API v1.1: `is_drill`),
        // damit die App Übungs-Benachrichtigungen als solche kennzeichnen kann.
        $drillRunIds = ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereIn('id', $notifications->pluck('scenario_run_id')->filter()->unique())
            ->where('mode', ScenarioRunMode::Drill->value)
            ->pluck('id')
            ->all();

        return $notifications
            ->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'scenario_run_id' => $n->scenario_run_id,
                'triggered_by_name' => $n->triggered_by_name,
                'severity' => $n->severity,
                'is_drill' => $n->scenario_run_id !== null && in_array($n->scenario_run_id, $drillRunIds, true),
                'created_at' => $n->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Laufende Notfall-Abläufe (weder beendet noch abgebrochen) inkl. ihrer
     * Schritte mit geteiltem Erledigt-Status. Grundlage für die „aktiver
     * Notfall"-Anzeige und den geteilten Fortschritt in der App.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function activeRuns(Company $company): array
    {
        // Name → Mobilnummer der Firmen-Mitarbeiter, um im Verlauf den handelnden
        // Nutzer direkt anrufbar zu machen (User selbst hat keine Telefonnummer).
        $phoneByName = Employee::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->get()
            ->filter(fn (Employee $e) => filled($e->mobile_phone))
            ->mapWithKeys(fn (Employee $e) => [mb_strtolower(trim($e->fullName())) => $e->mobile_phone]);

        return ScenarioRun::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNull('ended_at')
            ->whereNull('aborted_at')
            ->with([
                'startedBy',
                'steps' => fn ($q) => $q->orderBy('sort'),
                'steps.checkedBy',
                'crisisLogEntries.user',
                'acknowledgements.user',
            ])
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (ScenarioRun $run) => [
                'id' => $run->id,
                'scenario_id' => $run->scenario_id,
                'title' => $run->title,
                'mode' => $run->mode->value,
                'is_drill' => $run->isDrill(),
                'started_at' => $run->started_at?->toIso8601String(),
                'started_by' => $run->startedBy?->name,
                'source' => $run->source,
                'trigger_detail' => $run->trigger_detail,
                // Eskalations-Zeitpunkt (planb:escalate-unacknowledged-runs):
                // gesetzt, wenn ein echter Alarm nach Fristablauf ohne
                // Quittierung eskaliert wurde — sonst null.
                'escalated_at' => $run->escalated_at?->toIso8601String(),
                // Alarm-Quittierungen (API v1.1): wer den Alarm gesehen hat bzw.
                // übernimmt — max. eine je Nutzer, `taking_over` schlägt `seen`.
                // `user_id` im selben Format wie `user.id` im Login-Response,
                // damit die App den eigenen Status eindeutig erkennt (statt
                // unscharf per Namensvergleich).
                'acknowledgements' => $run->acknowledgements->map(fn (ScenarioRunAcknowledgement $ack) => [
                    'user_id' => (string) $ack->user_id,
                    'person' => $ack->user?->name,
                    'status' => $ack->status,
                    'acknowledged_at' => $ack->acknowledged_at?->toIso8601String(),
                ])->all(),
                'steps' => $run->steps->map(fn (ScenarioRunStep $step) => [
                    'id' => $step->id,
                    'position' => $step->sort,
                    'text' => trim($step->title.($step->description ? ' — '.$step->description : '')),
                    'role' => $step->responsible,
                    'checked' => $step->checked_at !== null,
                    'checked_at' => $step->checked_at?->toIso8601String(),
                    'checked_by' => $step->checkedBy?->name,
                    'note' => $step->note,
                ])->all(),
                // Verlauf/Historie des Laufs (wer wann was, App/Web) für die App.
                'log' => $run->crisisLogEntries->take(200)->map(fn (CrisisLogEntry $entry) => [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'source' => $entry->source,
                    'message' => $entry->message,
                    'user_name' => $entry->user?->name,
                    // Nutzer-Telefonnummern (aus dem Profil), Mobil ersatzweise über
                    // Mitarbeiter-Namensabgleich – zum Kontaktieren aus dem Verlauf.
                    'user_mobile' => $entry->user?->mobile_phone
                        ?: ($entry->user?->name ? ($phoneByName[mb_strtolower(trim($entry->user->name))] ?? null) : null),
                    'user_phone' => $entry->user?->phone,
                    'user_emergency' => $entry->user?->emergency_phone,
                    'occurred_at' => $entry->occurred_at?->toIso8601String(),
                ])->all(),
            ])
            ->all();
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
                // Koordinaten aus dem serverseitigen Geocoding (API v1.2);
                // null, solange die Adresse (noch) nicht aufgelöst wurde.
                'lat' => $location->lat,
                'lng' => $location->lng,
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
     * @param  list<array{system: System, level_name: ?string, level_sort: ?int, rto_minutes: ?int, depth: int, open_tasks: int, total_tasks: int}>  $recoveryOrder
     * @return array<int, array<string, mixed>>
     */
    private static function recoveryOrder(Company $company, array $recoveryOrder): array
    {
        $rows = [];
        $position = 1;

        // Zeitleisten-Daten (Start-/Endminute je System) — gleiche Quelle wie
        // die Backend-Gantt-Ansicht, für die Tablet-Zeitleiste der Apps.
        $timeline = RecoveryTimelineBuilder::build($company);
        $timelineBySystem = [];
        foreach ($timeline['entries'] as $entry) {
            $timelineBySystem[$entry['system']->id] = $entry;
        }

        // Abhängigkeits-Stufen wie auf /systems/recovery: Stufe 1 kann sofort
        // starten, Stufe n erst, wenn Stufe n-1 läuft. Gleiche Quelle wie die
        // Backend-Seite (RecoveryOrder::compute), damit App und Web identisch sind.
        $systems = collect($recoveryOrder)
            ->map(fn (array $item) => $item['system'])
            ->filter(fn ($system) => $system instanceof System)
            ->values();
        $plan = RecoveryOrder::compute($systems);
        $stageBySystem = [];
        foreach ($plan['stages'] as $index => $stageSystems) {
            foreach ($stageSystems as $stageSystem) {
                $stageBySystem[$stageSystem->id] = $index + 1;
            }
        }

        // Aufgaben aller Systeme in einem Rutsch laden (statt je Zeile).
        $systemIds = collect($recoveryOrder)
            ->map(fn (array $item) => $item['system'] instanceof System ? $item['system']->id : null)
            ->filter();
        $tasksBySystem = SystemTask::query()
            ->whereIn('system_id', $systemIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('system_id');

        foreach ($recoveryOrder as $item) {
            $system = $item['system'];
            $level = $system instanceof System ? $system->emergencyLevel : null;
            $tasks = $system instanceof System ? ($tasksBySystem->get($system->id) ?? collect()) : collect();

            $rows[] = [
                'position' => $position++,
                'system_id' => $system instanceof System ? $system->id : null,
                'system' => $system instanceof System ? $system->name : (string) $system,
                // Abhängigkeits-Kanten für die App-Chips („Braucht" / „Wird
                // gebraucht von") — Relationen sind im Cockpit bereits geladen.
                'depends_on' => $system instanceof System ? $system->dependencies->pluck('id')->all() : [],
                'dependents' => $system instanceof System ? $system->dependents->pluck('id')->all() : [],
                'rto_minutes' => $item['rto_minutes'],
                'level' => $item['level_name'],
                // Stufen-Infos (redundant je Zeile, damit die Apps ohne
                // zweite Collection gruppieren können).
                'level_sort' => $item['level_sort'] ?? null,
                'level_description' => $level?->description,
                'level_reaction' => $level?->reaction,
                // Kennzahlen wie im Backend-Wiederanlauf.
                'rpo_minutes' => $system instanceof System ? $system->rpo_minutes : null,
                // 1-basierte Start-Stufe (null bei zyklischen Abhängigkeiten).
                'stage' => $system instanceof System ? ($stageBySystem[$system->id] ?? null) : null,
                // Zeitleiste: Minuten ab Notfallbeginn (Start nach Abhängigkeiten).
                'start_minutes' => $system instanceof System ? ($timelineBySystem[$system->id]['start'] ?? null) : null,
                'end_minutes' => $system instanceof System ? ($timelineBySystem[$system->id]['end'] ?? null) : null,
                'rto_missing' => $system instanceof System ? (bool) ($timelineBySystem[$system->id]['rto_missing'] ?? false) : false,
                'depth' => $item['depth'] ?? 0,
                'open_tasks' => $item['open_tasks'] ?? 0,
                'total_tasks' => $item['total_tasks'] ?? 0,
                'description' => $system instanceof System ? $system->description : null,
                'fallback_process' => $system instanceof System ? $system->fallback_process : null,
                'runbook_reference' => $system instanceof System ? $system->runbook_reference : null,
                'location_detail' => $system instanceof System ? $system->location_detail : null,
                // Die konkreten Wiederanlauf-Aufgaben (Anzeige in der App;
                // abgehakt wird weiterhin im Backend/Cockpit).
                'tasks' => $tasks->map(fn ($task) => [
                    'title' => $task->title,
                    'done' => $task->completed_at !== null,
                ])->values()->all(),
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
                'description' => $scenario->description,
                'trigger' => $scenario->trigger,
                // Alarmkette (7 Freitext-Felder) — nur gefüllte Felder, sonst null,
                // damit die Apps den Abschnitt komplett ausblenden können.
                'alarm_chain' => array_filter([
                    'detector' => $scenario->alarm_chain_detector,
                    'first_contact' => $scenario->alarm_chain_first_contact,
                    'lead_role' => $scenario->alarm_chain_lead_role,
                    'providers' => $scenario->alarm_chain_providers,
                    'management' => $scenario->alarm_chain_management,
                    'authorities' => $scenario->alarm_chain_authorities,
                    'comms_approval' => $scenario->alarm_chain_comms_approval,
                ], fn ($value) => filled($value)) ?: null,
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
