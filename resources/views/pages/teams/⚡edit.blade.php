<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\TeamName;
use App\Support\Audit\AccountAudit;
use App\Support\TeamPermissions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public string $teamName = '';

    public array $teamData = [];

    public array $members = [];

    public array $invitations = [];

    public array $availableRoles = [];

    public bool $isCurrentTeam = false;

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
        $this->teamName = $team->name;

        $this->populateTeamData();
    }

    public function updateTeam(): void
    {
        Gate::authorize('update', $this->teamModel);

        $validated = $this->validate([
            'teamName' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $team = DB::transaction(function () use ($validated) {
            $team = Team::whereKey($this->teamModel->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $validated['teamName']]);

            return $team;
        });

        $this->teamModel = $team;

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Team updated.'));

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->fresh()->slug], navigate: true);
    }

    public function updateMember(int $userId, string $role): void
    {
        Gate::authorize('updateMember', $this->teamModel);

        $validated = Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::enum(TeamRole::class)],
        ])->validate();

        $membership = $this->teamModel->memberships()
            ->where('user_id', $userId)
            ->firstOrFail();

        $oldRole = $membership->role;
        $membership->update(['role' => TeamRole::from($validated['role'])]);

        if ($oldRole->value !== $validated['role']) {
            AccountAudit::record(
                action: 'member.role_changed',
                entityType: 'User',
                entityId: $userId,
                entityLabel: $membership->user?->name,
                companyId: $this->teamModel->company?->id,
                changes: ['role' => ['old' => $oldRole->value, 'new' => $validated['role']]],
            );
        }

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Member role updated.'));
    }

    public function resendInvitation(string $code): void
    {
        Gate::authorize('inviteMember', $this->teamModel);

        $invitation = $this->teamModel->invitations()
            ->whereNull('accepted_at')
            ->where('code', $code)
            ->firstOrFail();

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        AccountAudit::record(
            action: 'invitation.resent',
            entityType: 'TeamInvitation',
            entityId: $invitation->id,
            entityLabel: $invitation->email,
            companyId: $this->teamModel->company?->id,
        );

        Flux::toast(variant: 'success', text: __('Invitation resent.'));
    }

    public function reactivateMember(int $userId): void
    {
        Gate::authorize('removeMember', $this->teamModel);

        $membership = $this->teamModel->memberships()
            ->where('user_id', $userId)
            ->firstOrFail();

        $membership->update(['disabled_at' => null]);

        AccountAudit::record(
            action: 'member.reactivated',
            entityType: 'User',
            entityId: $userId,
            entityLabel: $membership->user?->name,
            companyId: $this->teamModel->company?->id,
        );

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Member reactivated.'));
    }

    private function populateTeamData(): void
    {
        $user = Auth::user();

        $team = $this->teamModel->fresh();

        $this->teamData = [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_personal' => $team->is_personal,
        ];

        // Letzter App-Sync je User: neuester nicht widerrufener Mobile-Token der Firma.
        $companyId = $team->company?->id;
        $mobileSyncByUser = [];
        if ($companyId !== null) {
            foreach (\App\Models\ApiToken::query()
                ->withoutGlobalScope(\App\Scopes\CurrentCompanyScope::class)
                ->where('company_id', $companyId)
                ->whereNull('revoked_at')
                ->whereNotNull('created_by_user_id')
                ->get() as $apiToken) {
                if (! $apiToken->hasScope('mobile') || $apiToken->last_synced_at === null) {
                    continue;
                }
                $uid = $apiToken->created_by_user_id;
                if (! isset($mobileSyncByUser[$uid]) || $apiToken->last_synced_at->gt($mobileSyncByUser[$uid])) {
                    $mobileSyncByUser[$uid] = $apiToken->last_synced_at;
                }
            }
        }

        $this->members = $team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role?->label(),
            'disabled_at' => $member->pivot->disabled_at,
            'is_disabled' => $member->pivot->isDisabled(),
            'is_disable_scheduled' => $member->pivot->isDisableScheduled(),
            'has_activity' => $member->hasActivity(),
            'last_synced_at' => isset($mobileSyncByUser[$member->id]) ? $mobileSyncByUser[$member->id]->toIso8601String() : null,
        ])->toArray();

        $this->invitations = $team->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'created_at' => $invitation->created_at->toISOString(),
            ])->toArray();

        $this->availableRoles = TeamRole::assignable();

        $this->isCurrentTeam = $user->isCurrentTeam($team);
    }

    public function render()
    {
        $teamName = $this->teamData['name'] ?? $this->teamModel->name;

        $title = $this->permissions->canUpdateTeam
            ? __('Edit :name', ['name' => $teamName])
            : __('View :name', ['name' => $teamName]);

        return $this->view()->title($title);
    }

    #[Computed]
    public function permissions(): TeamPermissions
    {
        return Auth::user()->toTeamPermissions($this->teamModel);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Teams') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Teams')" :subheading="__('Manage your team settings')" full-width>
        <div class="space-y-10">
            <div class="space-y-6">
                @if ($this->permissions->canUpdateTeam)
                    <div class="space-y-4">
                        <form wire:submit="updateTeam" class="space-y-6">
                            <flux:input wire:model="teamName" :label="__('Team name')" required data-test="team-name-input" />

                            <flux:button variant="primary" type="submit" data-test="team-save-button">
                                {{ __('Save') }}
                            </flux:button>
                        </form>
                    </div>
                @else
                    <div>
                        <flux:heading>{{ $teamData['name'] }}</flux:heading>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading>{{ __('Team members') }}</flux:heading>
                        @if ($this->permissions->canAddMember || $this->permissions->canUpdateMember || $this->permissions->canRemoveMember)
                            <flux:subheading>{{ __('Manage who belongs to this team') }}</flux:subheading>
                        @endif
                    </div>

                    @if ($this->permissions->canCreateInvitation)
                        <flux:modal.trigger name="invite-member">
                            <flux:button variant="primary" icon="user-plus" data-test="invite-member-button">
                                {{ __('Invite member') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                <div class="space-y-3">
                    @foreach ($members as $member)
                        <div
                            @class([
                                'flex items-center justify-between rounded-lg border p-4',
                                'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => ! $member['is_disabled'],
                                'border-amber-300/70 bg-amber-50 dark:border-amber-700/40 dark:bg-amber-900/10' => $member['is_disabled'],
                            ])
                            data-test="member-row"
                        >
                            <div class="flex items-center gap-4">
                                <flux:avatar :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium">{{ $member['name'] }}</span>
                                        @if ($member['is_disabled'])
                                            <flux:badge color="amber" size="sm">
                                                {{ __('Deactivated') }}@if ($member['disabled_at'])
                                                    — {{ \Illuminate\Support\Carbon::parse($member['disabled_at'])->setTimezone(config('app.timezone'))->format('d.m.Y') }}
                                                @endif
                                            </flux:badge>
                                        @elseif ($member['is_disable_scheduled'])
                                            <flux:badge color="amber" size="sm">
                                                {{ __('Deactivation scheduled') }} — {{ \Illuminate\Support\Carbon::parse($member['disabled_at'])->setTimezone(config('app.timezone'))->format('d.m.Y') }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                                    @if (! empty($member['last_synced_at']))
                                        @php($lastSync = \Illuminate\Support\Carbon::parse($member['last_synced_at'])->setTimezone(config('app.timezone')))
                                        <flux:text class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400">
                                            <flux:icon.device-phone-mobile class="mr-1 inline h-3 w-3" />
                                            {{ __('App-Sync') }}: {{ $lastSync->format('d.m.Y H:i:s') }} · {{ $lastSync->diffForHumans() }}
                                        </flux:text>
                                    @else
                                        <flux:text class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('App nicht verbunden') }}</flux:text>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="outline" size="sm" icon:trailing="chevron-down" data-test="member-role-trigger">
                                            {{ $member['role_label'] }}
                                        </flux:button>
                                        <flux:menu>
                                            @foreach ($availableRoles as $role)
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    wire:click="updateMember({{ $member['id'] }}, '{{ $role['value'] }}')"
                                                    data-test="member-role-option"
                                                >
                                                    {{ $role['label'] }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                @else
                                    <flux:badge color="zinc">{{ $member['role_label'] }}</flux:badge>
                                @endif

                                @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="ellipsis-horizontal"
                                            data-test="member-actions-trigger"
                                        />
                                        <flux:menu>
                                            @if ($member['is_disabled'] || $member['is_disable_scheduled'])
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    icon="arrow-path"
                                                    wire:click="reactivateMember({{ $member['id'] }})"
                                                    data-test="member-reactivate"
                                                >
                                                    {{ __('Reactivate') }}
                                                </flux:menu.item>
                                            @else
                                                <flux:modal.trigger name="disable-member-{{ $member['id'] }}">
                                                    <flux:menu.item
                                                        as="button"
                                                        type="button"
                                                        icon="pause-circle"
                                                        data-test="member-disable"
                                                    >
                                                        {{ __('Deactivate…') }}
                                                    </flux:menu.item>
                                                </flux:modal.trigger>
                                            @endif

                                            <flux:menu.separator />

                                            <flux:modal.trigger name="remove-member-{{ $member['id'] }}">
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    variant="danger"
                                                    icon="x-mark"
                                                    data-test="member-remove-button"
                                                >
                                                    {{ __('Remove from team') }}
                                                </flux:menu.item>
                                            </flux:modal.trigger>

                                            @if (! $member['has_activity'])
                                                <flux:modal.trigger name="delete-user-{{ $member['id'] }}">
                                                    <flux:menu.item
                                                        as="button"
                                                        type="button"
                                                        variant="danger"
                                                        icon="trash"
                                                        data-test="delete-user-trigger"
                                                    >
                                                        {{ __('Delete user') }}
                                                    </flux:menu.item>
                                                </flux:modal.trigger>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </div>
                        </div>

                        @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                            <livewire:pages::teams.remove-member-modal
                                :team="$teamModel"
                                :member-id="$member['id']"
                                :member-name="$member['name']"
                                :modal-name="'remove-member-'.$member['id']"
                                :key="'remove-member-modal-'.$member['id']"
                            />

                            @if (! $member['is_disabled'] && ! $member['is_disable_scheduled'])
                                <livewire:pages::teams.disable-member-modal
                                    :team="$teamModel"
                                    :member-id="$member['id']"
                                    :member-name="$member['name']"
                                    :modal-name="'disable-member-'.$member['id']"
                                    :key="'disable-member-modal-'.$member['id']"
                                />
                            @endif

                            @if (! $member['has_activity'])
                                <livewire:pages::teams.delete-user-modal
                                    :team="$teamModel"
                                    :member-id="$member['id']"
                                    :member-name="$member['name']"
                                    :modal-name="'delete-user-'.$member['id']"
                                    :key="'delete-user-modal-'.$member['id']"
                                />
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>

            @if (count($invitations) > 0)
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Pending invitations') }}</flux:heading>
                        <flux:subheading>{{ __('Invitations that have not been accepted yet') }}</flux:subheading>
                    </div>

                    <div class="space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="invitation-row">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="envelope" class="text-zinc-500" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $invitation['email'] }}</div>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $invitation['role_label'] }}</flux:text>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    @if ($this->permissions->canCreateInvitation)
                                        <flux:tooltip :content="__('Resend invitation')">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="paper-airplane"
                                                wire:click="resendInvitation('{{ $invitation['code'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="resendInvitation('{{ $invitation['code'] }}')"
                                                data-test="invitation-resend-button"
                                            />
                                        </flux:tooltip>
                                    @endif

                                    @if ($this->permissions->canCancelInvitation)
                                        <flux:modal.trigger name="cancel-invitation-{{ $invitation['code'] }}">
                                            <flux:tooltip :content="__('Cancel invitation')">
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="x-mark"
                                                    data-test="invitation-cancel-button"
                                                />
                                            </flux:tooltip>
                                        </flux:modal.trigger>
                                    @endif
                                </div>
                            </div>
                            @if ($this->permissions->canCancelInvitation)
                                <livewire:pages::teams.cancel-invitation-modal
                                    :team="$teamModel"
                                    :invitation-code="$invitation['code']"
                                    :invitation-email="$invitation['email']"
                                    :modal-name="'cancel-invitation-'.$invitation['code']"
                                    :key="'cancel-invitation-modal-'.$invitation['code']"
                                />
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->permissions->canDeleteTeam && ! $teamData['is_personal'])
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Delete team') }}</flux:heading>
                        <flux:subheading>{{ __('Permanently delete your team') }}</flux:subheading>
                    </div>

                    <div class="space-y-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-200/10 dark:bg-red-900/20 dark:text-red-100">
                        <div>
                            <p class="font-medium">{{ __('Warning') }}</p>
                            <p class="text-sm">{{ __('Please proceed with caution, this cannot be undone.') }}</p>
                        </div>

                        <flux:modal.trigger name="delete-team">
                            <flux:button variant="danger" data-test="delete-team-button">
                                {{ __('Delete team') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            @endif
        </div>
    </x-pages::settings.layout>

    @if ($this->permissions->canCreateInvitation)
        <livewire:pages::teams.invite-member-modal :team="$teamModel" />
    @endif

    @if ($this->permissions->canDeleteTeam && ! $teamData['is_personal'])
        <livewire:pages::teams.delete-team-modal :team="$teamModel" />
    @endif
</section>
