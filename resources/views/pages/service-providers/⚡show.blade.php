<?php

use App\Enums\RaciRole;
use App\Enums\SystemOwnership;
use App\Models\ServiceProvider;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dienstleister')] class extends Component {
    public ServiceProvider $provider;

    public bool $confirmingDelete = false;

    public function mount(ServiceProvider $provider): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        $provider->load([
            'systems' => fn ($q) => $q->orderBy('name'),
            'tasks.system' => fn ($q) => $q->orderBy('name'),
        ]);

        $this->provider = $provider;
    }

    public function delete()
    {
        $this->provider->delete();

        Flux::toast(variant: 'success', text: __('Dienstleister gelöscht.'));

        return redirect()->route('service-providers.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('service-providers.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Dienstleister') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="xl">{{ $provider->name }}</flux:heading>
                    @if ($provider->type)
                        <flux:badge :color="$provider->type->isAuthority() ? 'amber' : 'zinc'" size="sm">
                            {{ $provider->type->label() }}
                        </flux:badge>
                    @endif
                </div>
                @if ($provider->contact_name)
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Ansprechpartner:in') }}: {{ $provider->contact_name }}
                    </flux:text>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button icon="pencil" :href="route('service-providers.index')" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
                <flux:button variant="danger" icon="trash" wire:click="$set('confirmingDelete', true)">
                    {{ __('Löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Kontakt') }}</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                @if ($provider->hotline)
                    <div class="flex items-start gap-3">
                        <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hotline') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                <a href="tel:{{ $provider->hotline }}" class="hover:underline">{{ $provider->hotline }}</a>
                                @if ($provider->sla)
                                    <flux:badge color="zinc" size="sm" class="ml-2">{{ $provider->sla }}</flux:badge>
                                @endif
                            </dd>
                        </div>
                    </div>
                @endif
                @if ($provider->email)
                    <div class="flex items-start gap-3">
                        <flux:icon.envelope class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</dt>
                            <dd class="truncate text-zinc-900 dark:text-zinc-100">
                                <a href="mailto:{{ $provider->email }}" class="hover:underline">{{ $provider->email }}</a>
                            </dd>
                        </div>
                    </div>
                @endif
                @if (! $provider->hotline && ! $provider->email)
                    <flux:text class="text-sm text-zinc-500">{{ __('Keine Kontaktdaten erfasst.') }}</flux:text>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Vertragsdaten') }}</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                @if ($provider->contract_number)
                    <div class="flex items-start gap-3">
                        <flux:icon.document-text class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Vertragsnummer') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $provider->contract_number }}</dd>
                        </div>
                    </div>
                @endif
                @if ($provider->sla)
                    <div class="flex items-start gap-3">
                        <flux:icon.clock class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('SLA') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $provider->sla }}</dd>
                        </div>
                    </div>
                @endif
                @if ($provider->direct_order_limit !== null)
                    <div class="flex items-start gap-3">
                        <flux:icon.banknotes class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Direkt-Auftragslimit') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ number_format((float) $provider->direct_order_limit, 2, ',', '.') }} €</dd>
                        </div>
                    </div>
                @endif
                @if (! $provider->contract_number && ! $provider->sla && $provider->direct_order_limit === null)
                    <flux:text class="text-sm text-zinc-500">{{ __('Keine Vertragsdaten erfasst.') }}</flux:text>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <flux:heading size="lg">{{ __('Zugeordnete Systeme') }}</flux:heading>
            @if ($provider->systems->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">
                    {{ __('Keine direkten System-Zuordnungen — pflegen Sie diese am jeweiligen System.') }}
                </flux:text>
            @else
                <ul class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @foreach ($provider->systems as $system)
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
                $tasksBySystem = $provider->tasks
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
                                    @php
                                        $raci = RaciRole::tryFrom((string) ($task->pivot->raci_role ?? ''));
                                        $isDeputy = (bool) ($task->pivot->is_deputy ?? false);
                                    @endphp
                                    <li class="flex items-center justify-between gap-2 rounded px-2 py-0.5 text-xs transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300">
                                            <flux:icon name="check-circle" class="h-3.5 w-3.5 text-zinc-400" />
                                            {{ $task->title }}
                                            @if ($isDeputy)
                                                <flux:badge color="purple" size="sm">{{ __('Vertretung') }}</flux:badge>
                                            @endif
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

        @if ($provider->notes)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <flux:heading size="lg">{{ __('Notizen') }}</flux:heading>
                <flux:text class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $provider->notes }}</flux:text>
            </div>
        @endif
    </div>

    <div class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Zuletzt aktualisiert :at', ['at' => $provider->updated_at?->isoFormat('LLL')]) }}
    </div>

    <flux:modal wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Dienstleister löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden. Alle System- und Aufgaben-Zuordnungen dieses Dienstleisters werden mit entfernt.') }}</flux:subheading>
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
