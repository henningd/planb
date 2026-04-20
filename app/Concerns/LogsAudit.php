<?php

namespace App\Concerns;

use App\Models\AuditLogEntry;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Records a snapshot of every create/update/delete on the model into the
 * `audit_log_entries` table so companies have a history of who changed what.
 *
 * Opt in per-model by implementing:
 *  - `auditLabel(): ?string`   (optional, defaults to the model's `name`)
 *  - `auditExcluded(): array`  (optional list of attribute names to ignore)
 */
trait LogsAudit
{
    public static function bootLogsAudit(): void
    {
        static::created(function (Model $model) {
            self::writeAuditEntry($model, 'created', $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();

            if ($dirty === []) {
                return;
            }

            $changes = [];
            foreach ($dirty as $key => $newValue) {
                $changes[$key] = [
                    'old' => $model->getOriginal($key),
                    'new' => $newValue,
                ];
            }

            self::writeAuditEntry($model, 'updated', $changes);
        });

        static::deleted(function (Model $model) {
            self::writeAuditEntry($model, 'deleted', null);
        });
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    protected static function writeAuditEntry(Model $model, string $action, ?array $changes): void
    {
        $companyId = $model->company_id ?? CurrentCompany::id();
        if ($companyId === null) {
            return;
        }

        $excluded = method_exists($model, 'auditExcluded')
            ? $model->auditExcluded()
            : ['created_at', 'updated_at', 'id', 'company_id'];

        if ($changes !== null) {
            $changes = collect($changes)
                ->reject(fn ($_, $key) => in_array($key, $excluded, true))
                ->toArray();

            if ($changes === []) {
                return;
            }
        }

        AuditLogEntry::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'entity_type' => class_basename($model),
            'entity_id' => $model->getKey(),
            'entity_label' => self::resolveAuditLabel($model),
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    protected static function resolveAuditLabel(Model $model): ?string
    {
        if (method_exists($model, 'auditLabel')) {
            $label = $model->auditLabel();
            if ($label !== null) {
                return Str::limit($label, 200);
            }
        }

        foreach (['name', 'title', 'label', 'insurer'] as $candidate) {
            if (isset($model->{$candidate}) && is_string($model->{$candidate})) {
                return Str::limit($model->{$candidate}, 200);
            }
        }

        return null;
    }
}
