<?php

namespace App\Support;

use App\Models\AuditLogEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Baut die gefilterte AuditLogEntry-Query basierend auf den Query-Parametern.
 * Wird sowohl von der Audit-Log-Page (über die übergebenen Properties) als auch
 * vom Export-Controller (über den Request) genutzt, damit Filter konsistent
 * sind und doppelte Logik vermieden wird.
 */
class AuditLogFilter
{
    /**
     * Erzeugt eine bereits sortierte und gefilterte Query für AuditLogEntry.
     *
     * Erwartete Parameter (alle optional):
     *  - entity_type: exakter Match auf entity_type
     *  - action: 'created'|'updated'|'deleted'|'assignments'|sonst
     *  - search: Volltext über entity_label und user.name
     *  - from: ISO-Datum (Y-m-d) — created_at >= 00:00:00
     *  - to:   ISO-Datum (Y-m-d) — created_at <= 23:59:59
     *
     * @param  array<string, string|null>  $filters
     * @return Builder<AuditLogEntry>
     */
    public static function build(array $filters): Builder
    {
        $query = AuditLogEntry::query()->with('user')->orderByDesc('created_at');

        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $query->where('entity_type', $entityType);
        }

        $action = trim((string) ($filters['action'] ?? ''));
        if ($action === 'assignments') {
            $query->where(function (Builder $q): void {
                $q->where('action', 'like', '%.assigned')
                    ->orWhere('action', 'like', '%.unassigned');
            });
        } elseif ($action !== '') {
            $query->where('action', $action);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('entity_label', 'like', $like)
                    ->orWhereHas('user', function (Builder $u) use ($like): void {
                        $u->where('name', 'like', $like);
                    });
            });
        }

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '') {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '') {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }

    /**
     * Extrahiert die unterstützten Filter-Werte aus einem Request.
     *
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'entity_type' => (string) $request->query('entity_type', ''),
            'action' => (string) $request->query('action', ''),
            'search' => (string) $request->query('search', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }
}
