<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public Team $team;

    public ?int $memberId = null;

    public string $memberName = '';

    public string $modalName = 'delete-user';

    public function mount(
        Team $team,
        ?int $memberId = null,
        ?string $memberName = null,
        ?string $modalName = null,
    ): void {
        $this->team = $team;
        $this->memberId = $memberId;
        $this->memberName = $memberName ?? '';
        $this->modalName = $modalName ?? ($memberId ? "delete-user-{$memberId}" : 'delete-user');
    }

    public function deleteUser(): void
    {
        Gate::authorize('removeMember', $this->team);

        $user = User::findOrFail($this->memberId);

        $membership = $this->team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_if($membership->role === TeamRole::Owner, 403);
        abort_if($user->hasActivity(), 422, 'User has existing activity and cannot be hard-deleted.');

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        $this->dispatch('close-modal', name: $this->modalName);

        Flux::toast(variant: 'success', text: __('User deleted.'));

        $this->redirectRoute('teams.edit', ['team' => $this->team->slug], navigate: true);
    }
}; ?>

<flux:modal :name="$modalName" focusable class="max-w-lg">
    <form wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
            <flux:subheading>
                {{ __('This permanently removes the user account and all of their memberships. Only possible because they have no activity on record.') }}
            </flux:subheading>
        </div>

        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-700/30 dark:bg-red-900/20 dark:text-red-200">
            {{ __('Are you sure you want to permanently delete :name?', ['name' => $memberName]) }}
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" type="submit" data-test="delete-user-confirm">
                {{ __('Delete user') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
