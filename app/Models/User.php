<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'current_team_id', 'is_super_admin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'preferences' => 'array',
        ];
    }

    /**
     * Whether the named sidebar group is expanded for this user.
     * Defaults to false (collapsed) so first-time users always see the
     * compact sidebar.
     */
    public function isSidebarGroupExpanded(string $key): bool
    {
        return (bool) data_get($this->preferences ?? [], "sidebar.groups.{$key}", false);
    }

    /**
     * Persist the expanded/collapsed state of a sidebar group.
     */
    public function setSidebarGroupExpanded(string $key, bool $expanded): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, "sidebar.groups.{$key}", $expanded);
        $this->forceFill(['preferences' => $preferences])->save();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the Company that belongs to the user's current team, if any.
     */
    public function currentCompany(): ?Company
    {
        return $this->currentTeam?->company;
    }

    /**
     * True if the user is at least Admin on their current team.
     * Used to gate sensitive sections of the app (insurance, shares, audit log).
     */
    public function isCurrentTeamAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $team = $this->currentTeam;
        if (! $team) {
            return false;
        }

        return $this->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * True if the user has any activity on record (audit log, scenario runs/steps,
     * handbook shares, outgoing invitations). Used to decide whether a hard delete
     * of the user account is safe.
     */
    public function hasActivity(): bool
    {
        return DB::table('audit_log_entries')->where('user_id', $this->id)->exists()
            || DB::table('scenario_runs')->where('started_by_user_id', $this->id)->exists()
            || DB::table('scenario_run_steps')->where('checked_by_user_id', $this->id)->exists()
            || DB::table('handbook_shares')->where('created_by_user_id', $this->id)->exists()
            || DB::table('team_invitations')->where('invited_by', $this->id)->exists();
    }
}
