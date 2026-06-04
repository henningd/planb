<?php

namespace App\Support;

use App\Models\Company;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\DB;

/**
 * Berechnet Ausfallkosten je Stunde für einen Mandanten und vermeidet dabei
 * Doppelzählung: Ein als „Träger" markiertes System
 * ({@see System::$downtime_cost_from_dependents}) — z. B. die Stromversorgung —
 * zählt mit seinen eigenen Kosten nicht mehr, weil sein Schaden bereits über
 * die (transitiv) abhängigen Systeme abgebildet wird.
 *
 * Begriffe:
 *  - „abhängige Systeme" (dependents) eines Trägers C = alle Systeme, die direkt
 *    oder indirekt von C abhängen (Kante system_id → depends_on_system_id).
 *  - „effektive Eigenkosten" eines Systems = 0, wenn es ein Träger ist, sonst
 *    seine hinterlegten downtime_cost_per_hour.
 */
class DowntimeCost
{
    /**
     * @param  array<string, array{name: string, hourly: int, carrier: bool}>  $systems
     * @param  array<string, list<string>>  $dependents  carrierId => [direkte Abhängige]
     */
    private function __construct(
        private readonly array $systems,
        private readonly array $dependents,
    ) {}

    public static function forCompany(Company $company): self
    {
        $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->get(['id', 'name', 'downtime_cost_per_hour', 'downtime_cost_from_dependents']);

        $byId = [];
        foreach ($systems as $system) {
            $byId[$system->id] = [
                'name' => (string) $system->name,
                'hourly' => (int) ($system->downtime_cost_per_hour ?? 0),
                'carrier' => (bool) $system->downtime_cost_from_dependents,
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

    /**
     * Effektive Eigenkosten je Stunde: 0 bei Trägern, sonst hinterlegte Kosten.
     */
    public function effectiveOwnHourly(string $systemId): int
    {
        $system = $this->systems[$systemId] ?? null;
        if ($system === null || $system['carrier']) {
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
     * Aus den abhängigen Systemen abgeleitete Stundenkosten eines Trägers
     * (Summe der effektiven Eigenkosten aller transitiv Abhängigen).
     */
    public function derivedHourly(string $systemId): int
    {
        $sum = 0;
        foreach ($this->transitiveDependentIds($systemId) as $dependentId) {
            $sum += $this->effectiveOwnHourly($dependentId);
        }

        return $sum;
    }

    public function isCarrier(string $systemId): bool
    {
        return (bool) ($this->systems[$systemId]['carrier'] ?? false);
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
     * enthaltenen Trägers — die Menge, über die summiert wird.
     *
     * @param  list<string>  $selectedIds
     * @return list<string>
     */
    public function expandSelection(array $selectedIds): array
    {
        $union = [];
        foreach ($selectedIds as $id) {
            $union[$id] = true;
            if ($this->isCarrier($id)) {
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
