<?php

use App\Enums\RaciRole;
use App\Enums\SystemOwnership;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rolle')] class extends Component {
    public Role $role;

    public bool $confirmingDelete = false;

    public function mount(Role $role): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        $role->load([
            'employees' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'systems' => fn ($q) => $q->orderBy('name'),
            'systemTasks.system' => fn ($q) => $q->orderBy('name'),
        ]);

        $this->role = $role;
    }

    public function delete()
    {
        if ($this->role->isSystem()) {
            Flux::toast(variant: 'warning', text: __('Systemrollen können nicht gelöscht werden.'));
            $this->confirmingDelete = false;

            return null;
        }

        $this->role->delete();

        Flux::toast(variant: 'success', text: __('Rolle gelöscht.'));

        return redirect()->route('roles.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('roles.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Rollen') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $role->name }}</flux:heading>
                    @if ($role->isSystem())
                        <flux:badge color="indigo" size="sm" icon="shield-check">{{ __('Pflichtrolle (System)') }}</flux:badge>
                    @endif
                </div>
                @if ($role->description)
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $role->description }}</flux:text>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button icon="pencil" :href="route('roles.index').'#role-'.$role->id" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
                @unless ($role->isSystem())
                    <flux:button variant="danger" icon="trash" wire:click="$set('confirmingDelete', true)">
                        {{ __('Löschen') }}
                    </flux:button>
                @endunless
            </div>
        </div>
    </div>

    @php
        $mains = $role->employees->where('pivot.is_deputy', false);
        $deputies = $role->employees->where('pivot.is_deputy', true);
    @endphp

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Mitarbeiter') }}</flux:heading>

            @if ($role->employees->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">{{ __('Niemand zugeordnet.') }}</flux:text>
            @else
                <div class="mt-4 space-y-4 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ __('Hauptperson(en)') }}
                        </div>
                        @if ($mains->isEmpty())
                            <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Keine Hauptperson zugeordnet.') }}</flux:text>
                        @else
                            <ul class="mt-1 space-y-1">
                                @foreach ($mains as $emp)
                                    <li>
                                        <flux:link :href="route('employees.show', $emp)" wire:navigate>{{ $emp->nameLastFirst() }}</flux:link>
                                        @if ($emp->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $emp->position }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ __('Vertretung(en)') }}
                        </div>
                        @if ($deputies->isEmpty())
                            <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Keine Vertretung zugeordnet.') }}</flux:text>
                        @else
                            <ul class="mt-1 space-y-1">
                                @foreach ($deputies as $emp)
                                    <li>
                                        <flux:link :href="route('employees.show', $emp)" wire:navigate>{{ $emp->nameLastFirst() }}</flux:link>
                                        @if ($emp->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $emp->position }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Direkt zugeordnete Systeme') }}</flux:heading>
            @if ($role->systems->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">
                    {{ __('Keine direkten System-Zuordnungen — pflegen Sie diese am jeweiligen System.') }}
                </flux:text>
            @else
                <ul class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @foreach ($role->systems as $system)
                        @php
                            $ownership = SystemOwnership::tryFrom((string) ($system->pivot->ownership_kind ?? ''));
                            $isDeputy = (bool) ($system->pivot->is_deputy ?? false);
                            $note = (string) ($system->pivot->note ?? '');
                        @endphp
                        <li class="py-2">
                            <div class="flex items-center justify-between gap-3">
                                <flux:link :href="route('systems.show', $system)" wire:navigate class="text-zinc-900 dark:text-zinc-100">
                                    {{ $system->name }}
                                </flux:link>
                                <div class="flex items-center gap-1.5">
                                    @if ($ownership)
                                        <flux:badge :color="$ownership->badgeColor()" size="sm" :icon="$ownership->icon()">
                                            {{ $ownership->shortLabel() }}
                                        </flux:badge>
                                    @endif
                                    @if ($isDeputy)
                                        <flux:badge color="purple" size="sm">{{ __('Vertretung') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                            @if ($note !== '')
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $note }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <flux:heading size="lg">{{ __('Aufgaben') }}</flux:heading>
            @php
                $tasksBySystem = $role->systemTasks
                    ->filter(fn ($t) => $t->system !== null)
                    ->groupBy(fn ($t) => $t->system->name)
                    ->sortKeys();
            @endphp
            @if ($tasksBySystem->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">
                    {{ __('Keine Aufgaben zugeordnet — pflegen Sie diese am jeweiligen System.') }}
                </flux:text>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($tasksBySystem as $systemName => $tasks)
                        @php $firstSystem = $tasks->first()->system; @endphp
                        <div>
                            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <flux:link :href="route('systems.show', $firstSystem)" wire:navigate>{{ $systemName }}</flux:link>
                            </div>
                            <ul class="mt-1 space-y-0.5">
                                @foreach ($tasks->sortBy(fn ($t) => mb_strtolower($t->title)) as $task)
                                    @php $raci = RaciRole::tryFrom((string) ($task->pivot->raci_role ?? '')); @endphp
                                    <li class="flex items-center justify-between gap-2 rounded px-2 py-0.5 text-xs transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300">
                                            <flux:icon name="check-circle" class="h-3.5 w-3.5 text-zinc-400" />
                                            {{ $task->title }}
                                        </span>
                                        @if ($raci)
                                            <flux:badge :color="$raci->badgeColor()" size="sm" :title="$raci->description()">
                                                {{ $raci->value }} · {{ $raci->label() }}
                                            </flux:badge>
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-500">{{ __('— keine RACI —') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Zuletzt aktualisiert :at', ['at' => $role->updated_at?->isoFormat('LLL')]) }}
    </div>

    <flux:modal wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Rolle löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden. Alle Mitarbeiter-, System- und Aufgaben-Zuordnungen dieser Rolle werden mit entfernt.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:button type="button" variant="filled" wire:click="$set('confirmingDelete', false)">
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
