<?php

namespace App\Support\Privacy;

use App\Models\AuditLogEntry;
use App\Models\Team;
use App\Models\User;

/**
 * Builds a DSGVO Art. 15 data export for a single user account.
 *
 * The export is intentionally limited to data that the user themselves
 * created or that is directly attached to their identity (memberships
 * and the audit-log entries authored by them). Cross-tenant content
 * (handbook data of other companies) is not included.
 */
class AccountDataExporter
{
    /**
     * Build the export payload for the given user.
     *
     * @return array{
     *     generated_at: string,
     *     user: array<string, mixed>,
     *     memberships: array<int, array<string, mixed>>,
     *     audit_log_entries: array<int, array<string, mixed>>,
     * }
     */
    public function export(User $user): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'user' => $this->userPayload($user),
            'memberships' => $this->membershipsPayload($user),
            'audit_log_entries' => $this->auditLogPayload($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function membershipsPayload(User $user): array
    {
        return $user->allTeams()
            ->with('company')
            ->get()
            ->map(function (Team $team) {
                $role = $team->pivot->role ?? null;

                return [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'team_slug' => $team->slug,
                    'company_name' => $team->company?->name,
                    'role' => $role,
                    'is_personal' => (bool) $team->is_personal,
                    'joined_at' => $team->pivot->created_at?->toIso8601String(),
                    'disabled_at' => $team->pivot->disabled_at,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function auditLogPayload(User $user): array
    {
        return AuditLogEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (AuditLogEntry $entry): array => [
                'id' => $entry->id,
                'company_id' => $entry->company_id,
                'entity_type' => $entry->entity_type,
                'entity_id' => $entry->entity_id,
                'entity_label' => $entry->entity_label,
                'action' => $entry->action,
                'changes' => $entry->changes,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
