<?php

namespace App\Support;

use App\Models\AuditLogEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Schreibt temporale Pivots (Pivot-Tabellen mit assigned_at / removed_at /
 * assigned_by_user_id / removed_by_user_id) revisionssicher.
 *
 * Statt rows physisch zu löschen, wird removed_at gesetzt — historische
 * Zuordnungen bleiben damit für Audits nachvollziehbar. Bei einer Änderung
 * einer "Identitätsspalte" (z. B. raci_role) wird die bestehende row beendet
 * und eine neue eingefügt, sodass die Historie der RACI-Wechsel pro
 * Person/Rolle/System lückenlos erhalten bleibt.
 *
 * Zusätzlich wird für jede Mutation ein AuditLogEntry an die Eltern-Entität
 * geschrieben, damit der zentrale Audit-Log-Verlauf vollständig ist.
 */
class AssignmentSync
{
    /**
     * Spalten, deren Änderung als "neue Zuordnung" gewertet wird (Row-Rotation).
     * sort und note sind annotative und werden in place aktualisiert.
     *
     * Pivots ohne diese Spalten ignorieren den Eintrag automatisch
     * (identityChanged prüft via array_key_exists).
     *
     * @var list<string>
     */
    private const IDENTITY_COLUMNS = ['raci_role', 'is_deputy'];

    /**
     * Synchronisiert die aktive Menge der zugeordneten verbundenen IDs.
     *
     * @param  BelongsToMany<Model, Model>  $relation
     * @param  array<int|string, array<string, mixed>|int|string>  $desired
     *                                                                       Entweder ['<related_id>' => ['raci_role' => 'R', ...], ...]
     *                                                                       oder eine flache Liste von related_ids.
     */
    public static function sync(Model $parent, BelongsToMany $relation, array $desired): void
    {
        $normalized = self::normalize($desired);

        DB::transaction(function () use ($parent, $relation, $normalized) {
            $ctx = self::context($parent, $relation);
            $now = Carbon::now();
            $userId = Auth::id();

            $currentRows = self::activeRows($ctx);

            $desiredIds = array_keys($normalized);
            $currentIds = $currentRows->keys()->all();

            $toAdd = array_diff($desiredIds, $currentIds);
            $toRemove = array_diff($currentIds, $desiredIds);
            $toCheck = array_intersect($desiredIds, $currentIds);

            foreach ($toRemove as $relId) {
                $existing = (array) $currentRows[$relId];
                self::endRow($ctx, $existing['id'], $now, $userId);
                self::auditEvent($parent, $relation, 'unassigned', $relId, $existing);
            }

            foreach ($toAdd as $relId) {
                self::insertRow($ctx, $relId, $normalized[$relId], $now, $userId);
                self::auditEvent($parent, $relation, 'assigned', $relId, $normalized[$relId]);
            }

            foreach ($toCheck as $relId) {
                $existing = (array) $currentRows[$relId];
                $desiredAttrs = $normalized[$relId];

                if (self::identityChanged($existing, $desiredAttrs)) {
                    self::endRow($ctx, $existing['id'], $now, $userId);
                    self::auditEvent($parent, $relation, 'unassigned', $relId, $existing);
                    self::insertRow($ctx, $relId, $desiredAttrs, $now, $userId);
                    self::auditEvent($parent, $relation, 'assigned', $relId, $desiredAttrs);

                    continue;
                }

                $patch = [];
                foreach ($desiredAttrs as $col => $value) {
                    if (in_array($col, self::IDENTITY_COLUMNS, true)) {
                        continue;
                    }
                    if (($existing[$col] ?? null) !== $value) {
                        $patch[$col] = $value;
                    }
                }
                if ($patch !== []) {
                    $patch['updated_at'] = $now;
                    DB::table($ctx['table'])->where('id', $existing['id'])->update($patch);
                }
            }
        });
    }

    /**
     * Fügt einer Beziehung eine einzelne Zuordnung hinzu.
     * Existiert bereits eine aktive row mit identischen Identitätsspalten, passiert nichts.
     *
     * @param  BelongsToMany<Model, Model>  $relation
     * @param  array<string, mixed>  $attrs
     */
    public static function attach(Model $parent, BelongsToMany $relation, int|string $relatedId, array $attrs = []): void
    {
        DB::transaction(function () use ($parent, $relation, $relatedId, $attrs) {
            $ctx = self::context($parent, $relation);
            $now = Carbon::now();
            $userId = Auth::id();

            $existing = DB::table($ctx['table'])
                ->where($ctx['foreignKey'], $ctx['parentKey'])
                ->where($ctx['relatedKey'], $relatedId)
                ->whereNull('removed_at')
                ->first();

            if ($existing && ! self::identityChanged((array) $existing, $attrs)) {
                $patch = [];
                foreach ($attrs as $col => $value) {
                    if (in_array($col, self::IDENTITY_COLUMNS, true)) {
                        continue;
                    }
                    if (($existing->{$col} ?? null) !== $value) {
                        $patch[$col] = $value;
                    }
                }
                if ($patch !== []) {
                    $patch['updated_at'] = $now;
                    DB::table($ctx['table'])->where('id', $existing->id)->update($patch);
                }

                return;
            }

            if ($existing) {
                self::endRow($ctx, $existing->id, $now, $userId);
                self::auditEvent($parent, $relation, 'unassigned', $relatedId, (array) $existing);
            }

            self::insertRow($ctx, $relatedId, $attrs, $now, $userId);
            self::auditEvent($parent, $relation, 'assigned', $relatedId, $attrs);
        });
    }

    /**
     * Beendet die aktive Zuordnung; existiert keine, ist es ein No-op.
     *
     * @param  BelongsToMany<Model, Model>  $relation
     */
    public static function detach(Model $parent, BelongsToMany $relation, int|string $relatedId): void
    {
        DB::transaction(function () use ($parent, $relation, $relatedId) {
            $ctx = self::context($parent, $relation);

            $existing = DB::table($ctx['table'])
                ->where($ctx['foreignKey'], $ctx['parentKey'])
                ->where($ctx['relatedKey'], $relatedId)
                ->whereNull('removed_at')
                ->first();

            if (! $existing) {
                return;
            }

            self::endRow($ctx, $existing->id, Carbon::now(), Auth::id());
            self::auditEvent($parent, $relation, 'unassigned', $relatedId, (array) $existing);
        });
    }

    /**
     * @param  BelongsToMany<Model, Model>  $relation
     * @return array{table: string, foreignKey: string, relatedKey: string, parentKey: int|string, relation: BelongsToMany<Model, Model>}
     */
    private static function context(Model $parent, BelongsToMany $relation): array
    {
        $parentKey = $parent->getKey();
        if ($parentKey === null) {
            throw new RuntimeException('Cannot sync assignments on an unsaved parent model.');
        }

        return [
            'table' => $relation->getTable(),
            'foreignKey' => $relation->getForeignPivotKeyName(),
            'relatedKey' => $relation->getRelatedPivotKeyName(),
            'parentKey' => $parentKey,
            'relation' => $relation,
        ];
    }

    /**
     * @param  array{table: string, foreignKey: string, relatedKey: string, parentKey: int|string, relation: BelongsToMany<Model, Model>}  $ctx
     * @return Collection<int|string, object>
     */
    private static function activeRows(array $ctx): Collection
    {
        return DB::table($ctx['table'])
            ->where($ctx['foreignKey'], $ctx['parentKey'])
            ->whereNull('removed_at')
            ->get()
            ->keyBy($ctx['relatedKey']);
    }

    /**
     * @param  array{table: string, foreignKey: string, relatedKey: string, parentKey: int|string, relation: BelongsToMany<Model, Model>}  $ctx
     * @param  array<string, mixed>  $attrs
     */
    private static function insertRow(array $ctx, int|string $relatedId, array $attrs, Carbon $now, ?int $userId): void
    {
        $payload = $attrs;
        $payload['id'] = (string) Str::uuid();
        $payload[$ctx['foreignKey']] = $ctx['parentKey'];
        $payload[$ctx['relatedKey']] = $relatedId;
        $payload['assigned_at'] = $now;
        $payload['assigned_by_user_id'] = $userId;
        $payload['created_at'] = $now;
        $payload['updated_at'] = $now;

        DB::table($ctx['table'])->insert($payload);
    }

    /**
     * @param  array{table: string, foreignKey: string, relatedKey: string, parentKey: int|string, relation: BelongsToMany<Model, Model>}  $ctx
     */
    private static function endRow(array $ctx, string $rowId, Carbon $now, ?int $userId): void
    {
        DB::table($ctx['table'])->where('id', $rowId)->update([
            'removed_at' => $now,
            'removed_by_user_id' => $userId,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $desired
     */
    private static function identityChanged(array $existing, array $desired): bool
    {
        foreach (self::IDENTITY_COLUMNS as $col) {
            if (! array_key_exists($col, $desired)) {
                continue;
            }
            if (($existing[$col] ?? null) !== $desired[$col]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int|string, array<string, mixed>|int|string>  $desired
     * @return array<int|string, array<string, mixed>>
     */
    private static function normalize(array $desired): array
    {
        $out = [];
        foreach ($desired as $key => $value) {
            if (is_array($value)) {
                $out[$key] = $value;
            } else {
                $out[$value] = [];
            }
        }

        return $out;
    }

    /**
     * @param  BelongsToMany<Model, Model>  $relation
     * @param  array<string, mixed>  $pivotData
     */
    private static function auditEvent(Model $parent, BelongsToMany $relation, string $action, int|string $relatedId, array $pivotData): void
    {
        $companyId = $parent->company_id ?? CurrentCompany::id();
        if ($companyId === null) {
            return;
        }

        $relatedModel = $relation->getRelated()->newQuery()->find($relatedId);
        $relatedLabel = self::resolveLabel($relatedModel);

        $pivotPayload = collect($pivotData)
            ->only(['raci_role', 'sort', 'note'])
            ->reject(fn ($v) => $v === null || $v === '')
            ->all();

        AuditLogEntry::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'entity_type' => class_basename($parent),
            'entity_id' => $parent->getKey(),
            'entity_label' => self::resolveLabel($parent),
            'action' => "{$relation->getRelationName()}.{$action}",
            'changes' => [
                'related_type' => $relatedModel ? class_basename($relatedModel) : null,
                'related_id' => (string) $relatedId,
                'related_label' => $relatedLabel,
                'pivot' => $pivotPayload,
            ],
        ]);
    }

    private static function resolveLabel(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }
        if (method_exists($model, 'auditLabel')) {
            return Str::limit((string) $model->auditLabel(), 200);
        }
        if (method_exists($model, 'fullName')) {
            return Str::limit((string) $model->fullName(), 200);
        }
        foreach (['name', 'title', 'label'] as $candidate) {
            if (isset($model->{$candidate}) && is_string($model->{$candidate})) {
                return Str::limit($model->{$candidate}, 200);
            }
        }

        return null;
    }
}
