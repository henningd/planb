<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Einladung annehmen'), Layout('layouts.auth')] class extends Component {
    public TeamInvitation $invitation;

    public function mount(TeamInvitation $invitation): void
    {
        $this->invitation = $invitation->load('team', 'inviter');

        if (! Auth::check()) {
            session()->put('url.intended', route('invitations.accept', $invitation->code));
        }

        if ($this->state === 'ready') {
            $this->acceptInvitation();
        }
    }

    public function acceptInvitation(): void
    {
        $user = Auth::user();

        abort_unless($user && $this->state === 'ready', 403);

        $team = $this->invitation->team;

        DB::transaction(function () use ($user, $team) {
            $team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $this->invitation->role]
            );

            $this->invitation->update(['accepted_at' => now()]);

            $user->switchTeam($team);
        });

        session()->forget('url.intended');

        $this->redirectRoute('dashboard', ['current_team' => $team->slug]);
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('invitations.accept', ['invitation' => $this->invitation->code]);
    }

    #[Computed]
    public function state(): string
    {
        if ($this->invitation->isAccepted()) {
            return 'accepted';
        }

        if ($this->invitation->isExpired()) {
            return 'expired';
        }

        if (! Auth::check()) {
            return 'guest';
        }

        if (Str::lower(Auth::user()->email) !== Str::lower($this->invitation->email)) {
            return 'wrong_user';
        }

        return 'ready';
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2 text-center">
        <flux:heading size="xl">Einladung ins Team</flux:heading>
        <flux:subheading>
            <strong>{{ $invitation->inviter->name }}</strong> hat Sie eingeladen,
            dem Team <strong>„{{ $invitation->team->name }}"</strong> bei PlanB beizutreten.
        </flux:subheading>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
            <dt class="text-zinc-500 dark:text-zinc-400">Team</dt>
            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invitation->team->name }}</dd>

            <dt class="text-zinc-500 dark:text-zinc-400">Rolle</dt>
            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invitation->role->label() }}</dd>

            <dt class="text-zinc-500 dark:text-zinc-400">Einladung an</dt>
            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $invitation->email }}</dd>

            @if ($invitation->expires_at)
                <dt class="text-zinc-500 dark:text-zinc-400">Gültig bis</dt>
                <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $invitation->expires_at->copy()->setTimezone('Europe/Berlin')->format('d.m.Y \u\m H:i \U\h\r') }}
                </dd>
            @endif
        </dl>
    </div>

    @switch($this->state)
        @case('ready')
            <form wire:submit="acceptInvitation" class="flex flex-col gap-3">
                <flux:button type="submit" variant="primary" class="w-full">
                    Einladung annehmen
                </flux:button>
                <flux:subheading class="text-center">
                    Angemeldet als {{ Auth::user()->email }}
                </flux:subheading>
            </form>
            @break

        @case('guest')
            <div class="flex flex-col gap-3">
                <flux:button :href="route('login')" variant="primary" class="w-full" wire:navigate>
                    Anmelden, um anzunehmen
                </flux:button>
                <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                    Noch kein Konto?
                    <flux:link :href="route('register')" wire:navigate>Jetzt registrieren</flux:link>
                </div>
            </div>
            @break

        @case('wrong_user')
            <div class="flex flex-col gap-3">
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                    Diese Einladung wurde an <strong>{{ $invitation->email }}</strong> geschickt,
                    Sie sind aber als <strong>{{ Auth::user()->email }}</strong> angemeldet.
                    Bitte melden Sie sich ab und wechseln Sie das Konto.
                </div>
                <flux:button wire:click="logout" variant="primary" class="w-full">
                    Abmelden und Konto wechseln
                </flux:button>
            </div>
            @break

        @case('accepted')
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">
                Diese Einladung wurde bereits angenommen.
            </div>
            @if (Auth::check())
                <flux:button :href="route('dashboard', ['current_team' => $invitation->team->slug])" variant="primary" class="w-full" wire:navigate>
                    Zum Dashboard
                </flux:button>
            @endif
            @break

        @case('expired')
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-700 dark:bg-red-950/40 dark:text-red-200">
                Diese Einladung ist abgelaufen. Bitte bitten Sie {{ $invitation->inviter->name }}
                um eine neue Einladung.
            </div>
            @break
    @endswitch
</div>
