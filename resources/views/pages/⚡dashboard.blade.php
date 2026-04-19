<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\EmergencyLevel;
use App\Models\ScenarioRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()->currentCompany();
    }

    #[Computed]
    public function contactCount(): int
    {
        return $this->company ? Contact::count() : 0;
    }

    #[Computed]
    public function primaryContact(): ?Contact
    {
        return $this->company?->primaryContact();
    }

    #[Computed]
    public function emergencyLevelCount(): int
    {
        return $this->company ? EmergencyLevel::count() : 0;
    }

    #[Computed]
    public function recentContacts()
    {
        if (! $this->company) {
            return collect();
        }

        return Contact::orderByDesc('is_primary')->orderByDesc('created_at')->limit(5)->get();
    }

    #[Computed]
    public function activeRuns()
    {
        if (! $this->company) {
            return collect();
        }

        return ScenarioRun::whereNull('ended_at')
            ->whereNull('aborted_at')
            ->orderByDesc('started_at')
            ->get();
    }
}; ?>

<section class="flex w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div>
        <flux:heading size="xl">{{ __('Übersicht') }}</flux:heading>
        <flux:subheading>
            @if ($this->company)
                {{ __('Stand Ihres Notfallhandbuchs für :name.', ['name' => $this->company->name]) }}
            @else
                {{ __('Willkommen. Starten Sie mit dem Anlegen eines Firmenprofils.') }}
            @endif
        </flux:subheading>
    </div>

    {{-- Active runs banner --}}
    @if ($this->activeRuns->isNotEmpty())
        <div class="space-y-3">
            @foreach ($this->activeRuns as $run)
                <a href="{{ route('scenario-runs.show', $run) }}" wire:navigate
                   class="block rounded-xl border-2 p-5 {{ $run->mode->value === 'real' ? 'border-rose-400 bg-rose-50 dark:border-rose-800 dark:bg-rose-950/50' : 'border-indigo-400 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-950/50' }}">
                    <div class="flex items-center gap-3">
                        @if ($run->mode->value === 'real')
                            <flux:icon.exclamation-triangle class="h-6 w-6 text-rose-600 dark:text-rose-300" />
                            <div class="flex-1">
                                <div class="font-semibold text-rose-900 dark:text-rose-100">{{ __('Aktiver Ernstfall') }}</div>
                                <div class="text-sm text-rose-900/80 dark:text-rose-200/80">{{ $run->title }}</div>
                            </div>
                        @else
                            <flux:icon.academic-cap class="h-6 w-6 text-indigo-600 dark:text-indigo-300" />
                            <div class="flex-1">
                                <div class="font-semibold text-indigo-900 dark:text-indigo-100">{{ __('Laufende Übung') }}</div>
                                <div class="text-sm text-indigo-900/80 dark:text-indigo-200/80">{{ $run->title }}</div>
                            </div>
                        @endif
                        <flux:badge size="sm">{{ $run->started_at->diffForHumans() }}</flux:badge>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Onboarding hints --}}
    @if (! $this->company)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800 dark:bg-indigo-950/50">
            <div class="flex items-start gap-4">
                <flux:icon.sparkles class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600 dark:text-indigo-300" />
                <div class="flex-1">
                    <flux:heading size="base" class="text-indigo-900 dark:text-indigo-100">
                        {{ __('Firmenprofil anlegen') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-indigo-900/80 dark:text-indigo-200/80">
                        {{ __('Legen Sie zuerst die Basisdaten Ihres Unternehmens an. Danach können Sie Ansprechpartner und Eskalationsstufen pflegen.') }}
                    </flux:text>
                </div>
                <flux:button variant="primary" :href="route('company.edit')" wire:navigate>
                    {{ __('Jetzt anlegen') }}
                </flux:button>
            </div>
        </div>
    @elseif (! $this->primaryContact)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-700 dark:bg-amber-950/50">
            <div class="flex items-start gap-4">
                <flux:icon.exclamation-triangle class="mt-0.5 h-6 w-6 shrink-0 text-amber-600 dark:text-amber-300" />
                <div class="flex-1">
                    <flux:heading size="base" class="text-amber-900 dark:text-amber-100">
                        {{ __('Noch kein Hauptansprechpartner festgelegt') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-amber-900/80 dark:text-amber-200/80">
                        {{ __('Im Ernstfall muss klar sein, wer entscheidet. Legen Sie einen ersten Ansprechpartner an – er wird automatisch als Hauptansprechpartner markiert.') }}
                    </flux:text>
                </div>
                <flux:button variant="primary" :href="route('contacts.index')" wire:navigate>
                    {{ __('Kontakt anlegen') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Stat cards --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Firma') }}</flux:text>
                <flux:icon.building-office-2 class="h-5 w-5 text-zinc-400" />
            </div>
            <div class="mt-3 text-xl font-semibold">
                {{ $this->company?->name ?? __('Nicht angelegt') }}
            </div>
            @if ($this->company)
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->company->industry->label() }}
                    @if ($this->company->employee_count)
                        · {{ trans_choice(':count Mitarbeitender|:count Mitarbeitende', $this->company->employee_count, ['count' => $this->company->employee_count]) }}
                    @endif
                </flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Ansprechpartner') }}</flux:text>
                <flux:icon.users class="h-5 w-5 text-zinc-400" />
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-semibold">{{ $this->contactCount }}</span>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('angelegt') }}</flux:text>
            </div>
            @if ($this->primaryContact)
                <div class="mt-2 flex items-center gap-2 text-sm">
                    <flux:badge color="emerald" size="sm">{{ __('Hauptkontakt') }}</flux:badge>
                    <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $this->primaryContact->name }}</span>
                </div>
            @else
                <flux:text class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    {{ __('Kein Hauptansprechpartner') }}
                </flux:text>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Notfall-Level') }}</flux:text>
                <flux:icon.shield-exclamation class="h-5 w-5 text-zinc-400" />
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-semibold">{{ $this->emergencyLevelCount }}</span>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('definiert') }}</flux:text>
            </div>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Standard: Kritisch, Wichtig, Beobachten') }}
            </flux:text>
        </div>
    </div>

    {{-- Two columns: recent contacts + quick actions --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Zuletzt angelegte Kontakte') }}</flux:heading>
                <flux:link :href="route('contacts.index')" wire:navigate class="text-sm">
                    {{ __('Alle anzeigen') }}
                </flux:link>
            </div>
            @forelse ($this->recentContacts as $contact)
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:avatar :name="$contact->name" size="sm" />
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $contact->name }}</span>
                                @if ($contact->is_primary)
                                    <flux:badge color="emerald" size="sm">{{ __('Hauptansprechpartner') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $contact->role ?: __('Keine Rolle') }}
                            </flux:text>
                        </div>
                    </div>
                    <flux:badge color="zinc" size="sm">{{ $contact->type->label() }}</flux:badge>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Ansprechpartner angelegt.') }}
                    </flux:text>
                </div>
            @endforelse
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Schnellzugriff') }}</flux:heading>
            </div>
            <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                <flux:link :href="route('company.edit')" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.building-office-2 class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Firmenprofil') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </flux:link>
                <flux:link :href="route('contacts.index')" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.users class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Ansprechpartner') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </flux:link>
                <flux:link :href="route('emergency-levels.index')" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.shield-exclamation class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Notfall-Level') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </flux:link>
                @if ($this->company)
                    <a href="{{ route('handbook.print') }}" target="_blank" class="flex items-center gap-3 px-5 py-4 text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        <flux:icon.document-arrow-down class="h-5 w-5 text-zinc-500" />
                        <span class="flex-1 font-medium">{{ __('Handbuch als PDF') }}</span>
                        <flux:icon.arrow-top-right-on-square class="h-4 w-4 text-zinc-400" />
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
