<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public Team $team;

    public ?int $memberId = null;

    public string $memberName = '';

    public string $modalName = 'disable-member';

    public ?string $disabledOn = null;

    public function mount(
        Team $team,
        ?int $memberId = null,
        ?string $memberName = null,
        ?string $modalName = null,
    ): void {
        $this->team = $team;
        $this->memberId = $memberId;
        $this->memberName = $memberName ?? '';
        $this->modalName = $modalName ?? ($memberId ? "disable-member-{$memberId}" : 'disable-member');
    }

    public function disableMember(): void
    {
        Gate::authorize('removeMember', $this->team);

        $validated = $this->validate([
            'disabledOn' => ['nullable', 'date'],
        ]);

        $membership = $this->team->memberships()
            ->where('user_id', $this->memberId)
            ->firstOrFail();

        abort_if($membership->role === TeamRole::Owner, 403);

        $disabledAt = $validated['disabledOn']
            ? \Illuminate\Support\Carbon::parse($validated['disabledOn'], config('app.timezone'))->startOfDay()
            : now();

        $membership->update(['disabled_at' => $disabledAt]);

        if ($membership->user && $membership->user->isCurrentTeam($this->team) && $membership->isDisabled()) {
            $fallback = $membership->user->fallbackTeam(excluding: $this->team);
            if ($fallback) {
                $membership->user->switchTeam($fallback);
            } else {
                $membership->user->forceFill(['current_team_id' => null])->save();
            }
        }

        $this->reset('disabledOn');
        $this->dispatch('close-modal', name: $this->modalName);

        Flux::toast(variant: 'success', text: __('Member deactivated.'));

        $this->redirectRoute('teams.edit', ['team' => $this->team->slug], navigate: true);
    }
}; ?>

<flux:modal :name="$modalName" focusable class="max-w-lg">
    <form wire:submit="disableMember" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Deactivate member') }}</flux:heading>
            <flux:subheading>
                {{ __(':name loses access to this team. Membership is kept so you can reactivate later.', ['name' => $memberName]) }}
            </flux:subheading>
        </div>

        <flux:input
            type="date"
            wire:model="disabledOn"
            :label="__('Deactivated from')"
            :description="__('Leave empty to deactivate immediately.')"
            data-test="disable-member-date"
        />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" type="submit" data-test="disable-member-confirm">
                {{ __('Deactivate') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
