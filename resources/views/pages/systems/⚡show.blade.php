<?php

use App\Models\System;
use App\Support\Duration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('System')] class extends Component {
    public System $system;

    public function mount(System $system): void
    {
        abort_unless(Auth::user()->currentCompany(), 403);

        $this->system = $system->load([
            'priority',
            'serviceProviders',
            'employees',
            'dependencies',
            'dependents',
        ]);
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('systems.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Systeme') }}
        </flux:link>
    </div>

    <div class="mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl">{{ $system->name }}</flux:heading>
                    @if ($system->priority)
                        <flux:badge
                            :color="match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' }"
                        >
                            {{ $system->priority->name }}
                        </flux:badge>
                    @endif
                    <flux:badge color="zinc">{{ $system->category->label() }}</flux:badge>
                </div>
                @if ($system->description)
                    <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $system->description }}
                    </flux:text>
                @endif
            </div>

            <div class="flex shrink-0 gap-2">
                <flux:button size="sm" variant="filled" icon="qr-code" :href="route('systems.sticker', ['system' => $system->id])" target="_blank">
                    {{ __('QR-Aushang') }}
                </flux:button>
                <flux:button size="sm" variant="primary" icon="pencil" :href="route('systems.edit', ['system' => $system->id])" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
            </div>
        </div>

        @if ($system->rto_minutes || $system->rpo_minutes || $system->downtime_cost_per_hour)
            <div class="grid gap-4 border-b border-zinc-100 p-6 sm:grid-cols-3 dark:border-zinc-800">
                @if ($system->rto_minutes)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Max. Ausfall (RTO)') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ Duration::format($system->rto_minutes) }}</div>
                    </div>
                @endif
                @if ($system->rpo_minutes)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Max. Datenverlust (RPO)') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ Duration::format($system->rpo_minutes) }}</div>
                    </div>
                @endif
                @if ($system->downtime_cost_per_hour)
                    <div>
                        <flux:text class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Ausfallkosten') }}</flux:text>
                        <div class="mt-1 text-lg font-medium">{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / h</div>
                    </div>
                @endif
            </div>
        @endif

        <div x-data="{ tab: 'employees' }" class="p-6">
            <div role="tablist" class="mb-4 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === 'employees'"
                    @click="tab = 'employees'"
                    :class="tab === 'employees'
                        ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                >
                    <flux:icon.user class="h-4 w-4" />
                    {{ __('Verantwortliche Mitarbeiter') }}
                    <flux:badge color="teal" size="sm">{{ $system->employees->count() }}</flux:badge>
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === 'providers'"
                    @click="tab = 'providers'"
                    :class="tab === 'providers'
                        ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                >
                    <flux:icon.wrench-screwdriver class="h-4 w-4" />
                    {{ __('Dienstleister') }}
                    <flux:badge color="teal" size="sm">{{ $system->serviceProviders->count() }}</flux:badge>
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="tab === 'dependencies'"
                    @click="tab = 'dependencies'"
                    :class="tab === 'dependencies'
                        ? 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                        : 'border-b-2 border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium"
                >
                    <flux:icon.link class="h-4 w-4" />
                    {{ __('Abhängigkeiten') }}
                    <flux:badge color="teal" size="sm">{{ $system->dependencies->count() + $system->dependents->count() }}</flux:badge>
                </button>
            </div>

            <div x-show="tab === 'employees'" x-cloak>
                @if ($system->employees->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Noch keine Verantwortlichen zugewiesen.') }}</flux:text>
                @else
                    <ol class="space-y-2">
                        @foreach ($system->employees as $index => $e)
                            <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                        <span class="font-medium">{{ $e->fullName() }}</span>
                                        @if ($e->position)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $e->position }}</span>
                                        @endif
                                        @if ($e->department)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $e->department }}</span>
                                        @endif
                                    </div>
                                    @if ($e->mobile_phone || $e->work_phone || $e->email)
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($e->mobile_phone)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.device-phone-mobile class="h-3 w-3" />
                                                    {{ $e->mobile_phone }}
                                                </span>
                                            @endif
                                            @if ($e->work_phone)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.phone class="h-3 w-3" />
                                                    {{ $e->work_phone }}
                                                </span>
                                            @endif
                                            @if ($e->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.envelope class="h-3 w-3" />
                                                    {{ $e->email }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($e->pivot->note)
                                        <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                            {{ $e->pivot->note }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div x-show="tab === 'providers'" x-cloak>
                @if ($system->serviceProviders->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Keine Dienstleister zugeordnet.') }}</flux:text>
                @else
                    <ol class="space-y-2">
                        @foreach ($system->serviceProviders as $index => $p)
                            <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                        <span class="font-medium">{{ $p->name }}</span>
                                        @if ($p->contact_name)
                                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $p->contact_name }}</span>
                                        @endif
                                    </div>
                                    @if ($p->hotline || $p->email)
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($p->hotline)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.phone class="h-3 w-3" />
                                                    {{ $p->hotline }}
                                                </span>
                                            @endif
                                            @if ($p->email)
                                                <span class="inline-flex items-center gap-1">
                                                    <flux:icon.envelope class="h-3 w-3" />
                                                    {{ $p->email }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($p->pivot->note)
                                        <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                            {{ $p->pivot->note }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div x-show="tab === 'dependencies'" x-cloak class="space-y-5">
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Braucht:') }}</flux:heading>
                    @if ($system->dependencies->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dieses System hat keine Abhängigkeiten.') }}</flux:text>
                    @else
                        <ol class="space-y-2">
                            @foreach ($system->dependencies as $index => $dep)
                                <li class="flex items-start gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-semibold text-teal-800 dark:bg-teal-900 dark:text-teal-100">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                                            <span class="font-medium">{{ $dep->name }}</span>
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">· {{ $dep->category->label() }}</span>
                                        </div>
                                        @if ($dep->pivot->note)
                                            <div class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-400">
                                                {{ $dep->pivot->note }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Blockiert:') }}</flux:heading>
                    @if ($system->dependents->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Kein anderes System hängt davon ab.') }}</flux:text>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($system->dependents as $dep)
                                <flux:badge color="violet">{{ $dep->name }}</flux:badge>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
