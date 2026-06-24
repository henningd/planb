<?php

namespace App\Actions\ServiceProviders;

use App\Models\ServiceProvider;
use App\Support\AssignmentSync;
use App\Support\Audit\AccountAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Ersetzt Dienstleister A durch Dienstleister B (z. B. beim Anbieterwechsel):
 * Alle aktiven Zuordnungen von A wandern auf B.
 *
 * Übertragen werden die Verantwortlichkeiten von A auf Systeme und Aufgaben
 * (temporal & revisionssicher über AssignmentSync). A bleibt erhalten, nur
 * entkoppelt; B's bestehende (ggf. wichtigere) Zuordnungen werden nicht
 * überschrieben. Die eigene Lieferanten-Risikobewertung von A bleibt bei A.
 */
class ReplaceServiceProvider
{
    /**
     * @return array{systems: int, tasks: int}
     */
    public function handle(ServiceProvider $from, ServiceProvider $to): array
    {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Quelle und Ziel müssen unterschiedlich sein.');
        }

        if ($from->company_id !== $to->company_id) {
            throw new InvalidArgumentException('Beide Dienstleister müssen zum selben Mandanten gehören.');
        }

        $summary = DB::transaction(fn (): array => [
            'systems' => $this->transferPivot($from, $to, 'systems', ['raci_role', 'ownership_kind', 'is_deputy', 'sort', 'note']),
            'tasks' => $this->transferPivot($from, $to, 'tasks', ['raci_role', 'is_deputy']),
        ]);

        AccountAudit::record(
            action: 'service_provider.replaced',
            entityType: 'ServiceProvider',
            entityId: $from->id,
            entityLabel: $from->name,
            companyId: $from->company_id,
            changes: [
                'to_id' => $to->id,
                'to_label' => $to->name,
                'summary' => $summary,
            ],
        );

        return $summary;
    }

    /**
     * Verschiebt die aktiven Zuordnungen eines temporalen Pivots von A auf B.
     *
     * @param  list<string>  $attrCols
     */
    private function transferPivot(ServiceProvider $from, ServiceProvider $to, string $relationName, array $attrCols): int
    {
        $from->load($relationName);

        $count = 0;

        foreach ($from->{$relationName} as $related) {
            /** @var BelongsToMany<Model, ServiceProvider> $targetRelation */
            $targetRelation = $to->{$relationName}();

            if (! $targetRelation->whereKey($related->getKey())->exists()) {
                $attrs = [];
                foreach ($attrCols as $col) {
                    $attrs[$col] = $related->pivot->{$col} ?? null;
                }

                AssignmentSync::attach($to, $to->{$relationName}(), $related->getKey(), $attrs);
            }

            AssignmentSync::detach($from, $from->{$relationName}(), $related->getKey());

            $count++;
        }

        return $count;
    }
}
