<?php

namespace App\Support\Audit;

use App\Models\AuditLogEntry;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Schreibt organisatorische Vorgänge (Konto, Team, Firma, Sicherheit) in das
 * gemeinsame `audit_log_entries`-Log – analog zum LogsAudit-Trait, aber für
 * Aktionen, die nicht an ein einzelnes Eloquent-Model-Event hängen
 * (z.B. Einladungen, Rollenwechsel, 2FA).
 *
 * Logging darf den auslösenden Vorgang niemals unterbrechen: Persistenz-Fehler
 * werden gemeldet und verschluckt. Vorgänge ohne auflösbare company_id werden
 * still übersprungen – die Tabelle erfordert eine company_id und die
 * Mandanten-Ansicht könnte sie ohnehin nicht anzeigen.
 */
class AccountAudit
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(
        string $action,
        string $entityType,
        int|string $entityId,
        ?string $entityLabel = null,
        ?string $companyId = null,
        ?int $actorId = null,
        ?array $changes = null,
    ): void {
        $companyId ??= CurrentCompany::id();

        if ($companyId === null) {
            return;
        }

        try {
            AuditLogEntry::create([
                'company_id' => $companyId,
                'user_id' => $actorId ?? Auth::id(),
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'entity_label' => $entityLabel !== null ? Str::limit($entityLabel, 200) : null,
                'action' => $action,
                'changes' => $changes,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
