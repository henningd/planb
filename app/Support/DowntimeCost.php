<?php

namespace App\Support;

use App\Enums\DowntimeCostMode;
use App\Models\Company;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\DB;

/**
 * Berechnet Ausfallkosten je Stunde für einen Mandanten und vermeidet dabei
 * Doppelzählung. Pro System steuert {@see DowntimeCostMode}, wie die Kosten
 * einfließen:
 *  - Own                → nur eigene Kosten.
 *  - FromDependents     → eigene Kosten deaktiviert, Schaden aus den (transitiv)
 *                         abhängigen Systemen (z. B. Stromversorgung).
 *  - OwnPlusDependents  → eigene Kosten plus die der abhängigen Systeme.
 */
class DowntimeCost
{
    /**
     * @param  array<string, array{name: string, hourly: int, mode: DowntimeCostMode}>  $systems
     * @param  array<string, list<string>>  $dependents  systemId => [direkte Abhängige]
     */
    private function __construct(
        private readonly array $systems,
        private readonly array $dependents,
    ) {}

    public static function forCompany(Company $company): self
    {
        $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->get(['id', 'name', 'downtime_cost_per_hour', 'downtime_cost_mode']);

        $byId = [];
        foreach ($systems as $system) {
            $byId[$system->id] = [
                'name' => (string) $system->name,
                'hourly' => (int) ($system->downtime_cost_per_hour ?? 0),
                'mode' => $system->downtime_cost_mode ?? DowntimeCostMode::Own,
            ];
        }

        $edges = DB::table('system_dependencies')
            ->whereIn('system_id', array_keys($byId))
            ->get(['system_id', 'depends_on_system_id']);

        $dependents = [];
        foreach ($edges as $edge) {
            // system_id hängt von depends_on_system_id ab → ist dessen „dependent".
            $dependents[$edge->depends_on_system_id][] = $edge->system_id;
        }

        return new self($byId, $dependents);
    }

    public function modeOf(string $systemId): DowntimeCostMode
    {
        return $this->systems[$systemId]['mode'] ?? DowntimeCostMode::Own;
    }

    public function aggregatesDependents(string $systemId): bool
    {
        return $this->modeOf($systemId)->aggregatesDependents();
    }

    /**
     * Effektive Eigenkosten je Stunde: 0, wenn der Modus die eigenen Kosten
     * nicht mitzählt (FromDependents), sonst die hinterlegten Kosten.
     */
    public function effectiveOwnHourly(string $systemId): int
    {
        $system = $this->systems[$systemId] ?? null;
        if ($system === null || ! $system['mode']->countsOwn()) {
            return 0;
        }

        return $system['hourly'];
    }

    /**
     * Alle (transitiv) von $systemId abhängigen System-IDs, zyklensicher.
     *
     * @return list<string>
     */
    public function transitiveDependentIds(string $systemId): array
    {
        $seen = [];
        $stack = $this->dependents[$systemId] ?? [];

        while ($stack !== []) {
            $current = array_pop($stack);
            if (isset($seen[$current]) || $current === $systemId) {
                continue;
            }
            $seen[$current] = true;
            foreach ($this->dependents[$current] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $stack[] = $next;
                }
            }
        }

        return array_keys($seen);
    }

    /**
     * Aus den abhängigen Systemen abgeleitete Stundenkosten (Summe der
     * effektiven Eigenkosten aller transitiv Abhängigen).
     */
    public function derivedHourly(string $systemId): int
    {
        $sum = 0;
        foreach ($this->transitiveDependentIds($systemId) as $dependentId) {
            $sum += $this->effectiveOwnHourly($dependentId);
        }

        return $sum;
    }

    /**
     * Der für DIESES System anzuzeigende Stundenwert je nach Modus:
     *  - Own                → eigene Kosten.
     *  - FromDependents     → abgeleitet aus Abhängigen.
     *  - OwnPlusDependents  → eigene Kosten + abgeleitet.
     */
    public function displayHourly(string $systemId): int
    {
        return $this->effectiveOwnHourly($systemId)
            + ($this->aggregatesDependents($systemId) ? $this->derivedHourly($systemId) : 0);
    }

    public function name(string $systemId): string
    {
        return $this->systems[$systemId]['name'] ?? '';
    }

    public function exists(string $systemId): bool
    {
        return isset($this->systems[$systemId]);
    }

    /**
     * Erweitert eine Auswahl um die transitiv abhängigen Systeme jedes
     * enthaltenen Systems, das Abhängige einbezieht — die Menge, über die
     * summiert wird.
     *
     * @param  list<string>  $selectedIds
     * @return list<string>
     */
    public function expandSelection(array $selectedIds): array
    {
        $union = [];
        foreach ($selectedIds as $id) {
            $union[$id] = true;
            if ($this->aggregatesDependents($id)) {
                foreach ($this->transitiveDependentIds($id) as $dependentId) {
                    $union[$dependentId] = true;
                }
            }
        }

        return array_keys($union);
    }

    /**
     * Gesamte Stundenkosten — doppelzählungsfrei. Ohne Auswahl: alle Systeme.
     *
     * @param  list<string>|null  $selectedIds
     */
    public function totalHourly(?array $selectedIds = null): int
    {
        $ids = $selectedIds ?? array_keys($this->systems);
        $sum = 0;
        foreach ($this->expandSelection($ids) as $id) {
            $sum += $this->effectiveOwnHourly($id);
        }

        return $sum;
    }

    /**
     * Aufschlüsselung nach echten Kostenträgern (effektive Eigenkosten > 0),
     * absteigend nach Stundenrate.
     *
     * @return list<array{system_id: string, system_name: string, hourly: int}>
     */
    public function perSystemBreakdown(): array
    {
        $rows = [];
        foreach ($this->systems as $id => $system) {
            $hourly = $this->effectiveOwnHourly($id);
            if ($hourly > 0) {
                $rows[] = [
                    'system_id' => (string) $id,
                    'system_name' => $system['name'],
                    'hourly' => $hourly,
                ];
            }
        }

        usort($rows, fn (array $a, array $b) => $b['hourly'] <=> $a['hourly']);

        return $rows;
    }
}
