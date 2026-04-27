<?php

use App\Enums\CommunicationChannel;
use App\Models\Company;
use App\Models\ScenarioRunStep;
use App\Support\Incident\Cockpit;
use App\Support\Incident\CockpitData;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Krisen-Cockpit')] class extends Component {
    public ?string $previewTemplateId = null;

    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()?->currentCompany();
    }

    #[Computed]
    public function cockpit(): ?CockpitData
    {
        $company = $this->company;
        if (! $company) {
            return null;
        }

        return Cockpit::for($company);
    }

    #[Computed]
    public function isEnabled(): bool
    {
        $company = $this->company;
        if (! $company) {
            return false;
        }

        return Cockpit::isEnabledFor($company);
    }

    /**
     * Beendet den aktuell laufenden ScenarioRun (setzt ended_at = now()).
     */
    public function endRun(): void
    {
        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null) {
            return;
        }

        $run->update(['ended_at' => now()]);
        unset($this->cockpit);

        Flux::toast(variant: 'success', text: __('Szenario beendet.'));
    }

    /**
     * Hakt einen Schritt ab oder entfernt das Häkchen wieder.
     */
    public function toggleStep(string $id): void
    {
        $step = ScenarioRunStep::find($id);
        if ($step === null) {
            return;
        }

        $cockpit = $this->cockpit;
        $run = $cockpit?->activeRun;
        if ($run === null || $step->scenario_run_id !== $run->id) {
            return;
        }

        if ($step->checked_at === null) {
            $step->update([
                'checked_at' => now(),
                'checked_by_user_id' => Auth::id(),
            ]);
        } else {
            $step->update([
                'checked_at' => null,
                'checked_by_user_id' => null,
            ]);
        }

        unset($this->cockpit);
    }

    public function openTemplate(string $id): void
    {
        $this->previewTemplateId = $id;
        Flux::modal('cockpit-template-preview')->show();
    }

    /**
     * @return \App\Models\CommunicationTemplate|null
     */
    #[Computed]
    public function previewTemplate()
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return $this->cockpit?->communicationTemplates->firstWhere('id', $this->previewTemplateId);
    }

    /**
     * Formatiert eine Minutenanzahl als „4h 0m" / „45m".
     */
    public function formatRto(?int $minutes): string
    {
        if ($minutes === null) {
            return '–';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return $mins.'m';
        }

        return $hours.'h '.$mins.'m';
    }

    /**
     * Farbe für die Notfall-Level-Badge nach sort-Reihenfolge (1 = höchster Schweregrad).
     */
    public function levelBadgeColor(?int $sort): string
    {
        return match ($sort) {
            1 => 'red',
            2 => 'amber',
            3 => 'sky',
            4 => 'emerald',
            default => 'zinc',
        };
    }
}; ?>

<section class="w-full">
    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @elseif (! $this->isEnabled)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Live-Inzident-Modus deaktiviert') }}</flux:heading>
            <flux:subheading>
                {{ __('Aktivieren Sie den Modus in den Systemeinstellungen, um im Ernstfall ein reduziertes Krisen-Cockpit zu sehen.') }}
            </flux:subheading>
            <flux:button class="mt-4" :href="route('system-settings.index')" wire:navigate icon="cog-8-tooth">
                {{ __('Zu den Einstellungen') }}
            </flux:button>
        </div>
    @else
        @if (! $this->cockpit->hasActiveRun())
            <div class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ __('Krisen-Cockpit') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Reduzierte Sicht für den Ernstfall – Krisenstab, Wiederanlauf-Reihenfolge, Schritte und Meldepflichten.') }}
                    </flux:subheading>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div class="flex items-start gap-3">
                        <flux:icon.shield-check class="mt-0.5 h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        <div>
                            <flux:heading size="lg" class="text-emerald-900 dark:text-emerald-100">{{ __('Kein aktiver Notfall') }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                                {{ __('Wenn ein Szenario gestartet wird (z. B. von der Szenarien-Seite), öffnet sich hier automatisch das Krisen-Cockpit.') }}
                            </flux:text>
                            <flux:button class="mt-4" variant="ghost" :href="route('scenarios.index')" wire:navigate>
                                {{ __('Szenarien öffnen') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php
                $cockpit = $this->cockpit;
                $run = $cockpit->activeRun;
                $scenarioName = $run->scenario?->name ?? $run->title ?? '–';
                $trigger = $run->scenario?->trigger ?? $run->title ?? null;
                $startedAtIso = $run->started_at?->toIso8601String();
            @endphp

            <div class="space-y-6">
                {{-- Sektion 1: Lage-Header (sticky) --}}
                <div
                    class="sticky top-0 z-30 -mx-4 rounded-xl border-l-4 border border-l-rose-500 border-rose-200 bg-rose-50 px-6 py-5 text-rose-950 shadow-sm dark:border-rose-900 dark:border-l-rose-500 dark:bg-rose-950/40 dark:text-rose-50 sm:mx-0"
                    x-data="{
                        startedAt: @js($startedAtIso),
                        elapsed: '00:00',
                        tick() {
                            if (! this.startedAt) { this.elapsed = '–'; return; }
                            const start = new Date(this.startedAt).getTime();
                            const diff = Math.max(0, Math.floor((Date.now() - start) / 1000));
                            const h = Math.floor(diff / 3600);
                            const m = Math.floor((diff % 3600) / 60);
                            const s = diff % 60;
                            const pad = (n) => n.toString().padStart(2, '0');
                            this.elapsed = h > 0
                                ? `${pad(h)}:${pad(m)}:${pad(s)}`
                                : `${pad(m)}:${pad(s)}`;
                        }
                    }"
                    x-init="tick(); setInterval(() => tick(), 1000)"
                    data-test="cockpit-header"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 text-2xl font-bold leading-tight">
                                <flux:icon.exclamation-triangle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                                <span>{{ __('Aktiver Notfall:') }}</span>
                                <span class="truncate" data-test="cockpit-scenario-name">{{ $scenarioName }}</span>
                            </div>
                            @if ($trigger && $trigger !== $scenarioName)
                                <p class="mt-1 text-sm text-rose-900/80 dark:text-rose-100/80">
                                    <span class="font-semibold">{{ __('Auslöser:') }}</span>
                                    {{ $trigger }}
                                </p>
                            @endif
                            <p class="mt-2 flex items-center gap-2 text-sm">
                                <flux:icon.clock class="h-4 w-4" />
                                <span>{{ __('Laufzeit:') }}</span>
                                <span class="font-mono text-base font-bold tabular-nums" x-text="elapsed">00:00</span>
                            </p>
                        </div>

                        <div class="shrink-0">
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="endRun"
                                wire:confirm="{{ __('Szenario wirklich beenden?') }}"
                                icon="x-circle"
                                data-test="cockpit-end-run"
                            >
                                {{ __('Szenario beenden') }}
                            </flux:button>
                        </div>
                    </div>
                </div>

                {{-- Sektion 2: Krisenstab --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.users class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Krisenstab') }}</flux:heading>
                    </div>

                    @if (count($cockpit->crisisStaff) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Kein Krisenstab hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5" data-test="cockpit-crisis-staff">
                            @foreach ($cockpit->crisisStaff as $member)
                                @php
                                    $main = $member['main'] ?? null;
                                    $deputies = $member['deputies'] ?? collect();
                                @endphp
                                <div class="flex flex-col rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                    <flux:badge color="zinc" size="sm" class="self-start">
                                        {{ $member['role_label'] ?? '' }}
                                    </flux:badge>

                                    @if ($main)
                                        <div class="mt-3">
                                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $main->fullName() }}
                                            </div>
                                            @if ($main->position)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $main->position }}</div>
                                            @endif
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @if ($main->mobile_phone)
                                                    <flux:button size="xs" variant="filled" icon="device-phone-mobile"
                                                        href="tel:{{ $main->mobile_phone }}">
                                                        {{ __('Mobil') }}
                                                    </flux:button>
                                                @endif
                                                @if ($main->work_phone)
                                                    <flux:button size="xs" variant="filled" icon="phone"
                                                        href="tel:{{ $main->work_phone }}">
                                                        {{ __('Festnetz') }}
                                                    </flux:button>
                                                @endif
                                                @if ($main->email)
                                                    <flux:button size="xs" variant="filled" icon="envelope"
                                                        href="mailto:{{ $main->email }}">
                                                        {{ __('E-Mail') }}
                                                    </flux:button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-3 rounded border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                                            {{ __('Keine Hauptperson hinterlegt') }}
                                        </div>
                                    @endif

                                    @if ($deputies->isNotEmpty())
                                        <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                                {{ __('Vertretung') }}
                                            </div>
                                            <div class="space-y-2">
                                                @foreach ($deputies as $deputy)
                                                    <div class="text-xs">
                                                        <div class="font-medium text-zinc-700 dark:text-zinc-200">
                                                            {{ $deputy->fullName() }}
                                                        </div>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            @if ($deputy->mobile_phone)
                                                                <flux:button size="xs" variant="ghost" icon="device-phone-mobile"
                                                                    href="tel:{{ $deputy->mobile_phone }}">
                                                                    {{ __('Mobil') }}
                                                                </flux:button>
                                                            @endif
                                                            @if ($deputy->work_phone)
                                                                <flux:button size="xs" variant="ghost" icon="phone"
                                                                    href="tel:{{ $deputy->work_phone }}">
                                                                    {{ __('Tel.') }}
                                                                </flux:button>
                                                            @endif
                                                            @if ($deputy->email)
                                                                <flux:button size="xs" variant="ghost" icon="envelope"
                                                                    href="mailto:{{ $deputy->email }}">
                                                                    {{ __('Mail') }}
                                                                </flux:button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sektion 3: Wiederanlauf-Reihenfolge --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.arrow-path class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Wiederanlauf-Reihenfolge') }}</flux:heading>
                    </div>

                    @if (count($cockpit->recoveryOrder) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Systeme hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="overflow-x-auto" data-test="cockpit-recovery-list">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-left text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                        <th class="py-2 pl-3 pr-3 font-semibold">{{ __('Notfall-Level') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('System') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('RTO') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('Frist') }}</th>
                                        <th class="py-2 pr-3 font-semibold">{{ __('Aufgaben') }}</th>
                                        <th class="py-2 pr-3 font-semibold sr-only">{{ __('Aktion') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($cockpit->recoveryOrder as $item)
                                        @php
                                            $sys = $item['system'];
                                            $deadline = $item['deadline_at'] ?? null;
                                            $deadlineIso = $deadline?->toIso8601String();
                                            $deadlineMissed = $deadline !== null && $deadline->isPast();
                                            $badgeColor = $this->levelBadgeColor($item['level_sort'] ?? null);
                                            $depthIndent = max(0, ($item['depth'] ?? 0)) * 12;
                                        @endphp
                                        <tr class="align-top {{ $deadlineMissed ? 'bg-rose-50 dark:bg-rose-950/30' : '' }}">
                                            <td class="py-[17px] pl-3 pr-3">
                                                <flux:badge color="{{ $badgeColor }}" size="sm">
                                                    {{ $item['level_name'] ?? __('—') }}
                                                </flux:badge>
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                <div style="padding-left: {{ $depthIndent }}px" class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $sys->name }}
                                                </div>
                                            </td>
                                            <td class="py-[17px] pr-3 font-mono tabular-nums text-zinc-700 dark:text-zinc-200">
                                                {{ $this->formatRto($item['rto_minutes'] ?? null) }}
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                @if ($deadline)
                                                    <span
                                                        class="inline-flex items-center gap-1.5 font-mono text-xs tabular-nums {{ $deadlineMissed ? 'animate-pulse rounded bg-rose-100 px-1.5 py-0.5 text-rose-800 dark:bg-rose-900/60 dark:text-rose-100' : 'text-zinc-700 dark:text-zinc-200' }}"
                                                        x-data="{
                                                            deadline: @js($deadlineIso),
                                                            display: '–',
                                                            tick() {
                                                                if (! this.deadline) { this.display = '–'; return; }
                                                                const target = new Date(this.deadline).getTime();
                                                                let diff = Math.floor((target - Date.now()) / 1000);
                                                                const sign = diff < 0 ? '-' : '';
                                                                diff = Math.abs(diff);
                                                                const h = Math.floor(diff / 3600);
                                                                const m = Math.floor((diff % 3600) / 60);
                                                                const s = diff % 60;
                                                                const pad = (n) => n.toString().padStart(2, '0');
                                                                if (diff < 3600) {
                                                                    this.display = `${sign}${pad(m)}:${pad(s)}`;
                                                                } else {
                                                                    this.display = `${sign}${pad(h)}:${pad(m)}`;
                                                                }
                                                            }
                                                        }"
                                                        x-init="tick(); setInterval(() => tick(), 30000)"
                                                        x-text="display"
                                                    >–</span>
                                                @else
                                                    <span class="text-zinc-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-[17px] pr-3">
                                                @php
                                                    $open = (int) ($item['open_tasks'] ?? 0);
                                                    $total = (int) ($item['total_tasks'] ?? 0);
                                                    $taskBadge = $open === 0 ? 'emerald' : ($open >= ($total === 0 ? 1 : $total) ? 'red' : 'amber');
                                                @endphp
                                                <flux:badge color="{{ $taskBadge }}" size="sm">
                                                    {{ $open }}/{{ $total }}
                                                </flux:badge>
                                            </td>
                                            <td class="py-[17px] pr-3 text-right">
                                                <flux:button size="xs" variant="ghost" icon="arrow-top-right-on-square"
                                                    :href="route('systems.show', ['system' => $sys->id])" wire:navigate>
                                                    {{ __('Öffnen') }}
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Sektion 3b: Aktuell laufender Schaden --}}
                @php
                    $damageRate = (int) $cockpit->damageRatePerHourEur;
                    $damageRateFormatted = number_format($damageRate, 0, ',', '.');
                    $topDamageSystems = array_slice($cockpit->damageRatePerSystem, 0, 5);
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" data-test="cockpit-damage">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.banknotes class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Aktuell laufender Schaden') }}</flux:heading>
                    </div>

                    @if ($damageRate === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Ausfallkosten je Stunde an Systemen hinterlegt.') }}
                        </flux:text>
                        <flux:button class="mt-3" size="xs" variant="ghost" :href="route('systems.index')" wire:navigate icon="arrow-top-right-on-square">
                            {{ __('Zu den Systemen') }}
                        </flux:button>
                    @else
                        <div
                            x-data="{
                                startedAt: @js($startedAtIso),
                                ratePerHour: {{ $damageRate }},
                                accumulated: '0',
                                tick() {
                                    if (! this.startedAt) { this.accumulated = '0'; return; }
                                    const start = new Date(this.startedAt).getTime();
                                    const elapsedSeconds = Math.max(0, (Date.now() - start) / 1000);
                                    const ratePerSecond = this.ratePerHour / 3600;
                                    const value = Math.floor(elapsedSeconds * ratePerSecond);
                                    this.accumulated = value.toLocaleString('de-DE');
                                }
                            }"
                            x-init="tick(); setInterval(() => tick(), 1000)"
                        >
                            <div class="font-mono text-4xl font-bold tabular-nums text-rose-600 dark:text-rose-400">
                                <span x-text="accumulated" data-test="cockpit-damage-counter">0</span>
                                <span> €</span>
                            </div>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('aktuell') }} <span class="font-semibold">{{ $damageRateFormatted }} €/h</span> &times; {{ __('Laufzeit') }}
                            </flux:text>
                        </div>

                        @if (count($topDamageSystems) > 0)
                            <div class="mt-5">
                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    {{ __('Top 5 Systeme nach Stundenrate') }}
                                </div>
                                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-damage-top">
                                    @foreach ($topDamageSystems as $entry)
                                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                            <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">
                                                {{ $entry['system_name'] }}
                                            </span>
                                            <span class="font-mono tabular-nums text-zinc-700 dark:text-zinc-200">
                                                {{ number_format($entry['hourly'], 0, ',', '.') }} €/h
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Sektion 4: Schritte abhaken --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.list-bullet class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Schritte') }}</flux:heading>
                    </div>

                    @if ($cockpit->steps->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Schritte für diesen Lauf hinterlegt.') }}
                        </flux:text>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-steps">
                            @foreach ($cockpit->steps as $step)
                                @php
                                    $checked = $step->checked_at !== null;
                                @endphp
                                <li class="flex items-start gap-3 py-3 {{ $checked ? 'opacity-60' : '' }}">
                                    <button
                                        type="button"
                                        wire:click="toggleStep('{{ $step->id }}')"
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded border {{ $checked ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-zinc-300 bg-white hover:border-rose-400 dark:border-zinc-600 dark:bg-zinc-800' }}"
                                        aria-label="{{ $checked ? __('Schritt rückgängig machen') : __('Schritt abhaken') }}"
                                        data-test="cockpit-step-toggle"
                                    >
                                        @if ($checked)
                                            <flux:icon.check class="h-4 w-4" />
                                        @endif
                                    </button>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <div class="font-medium {{ $checked ? 'text-zinc-500 line-through dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                                {{ $step->title }}
                                            </div>
                                            @if ($step->responsible)
                                                <div class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $step->responsible }}
                                                </div>
                                            @endif
                                        </div>
                                        @if ($step->description)
                                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                {{ $step->description }}
                                            </div>
                                        @endif
                                        @if ($checked && $step->checked_at)
                                            <div class="mt-1 text-xs text-zinc-400">
                                                {{ __('Erledigt') }}: {{ $step->checked_at->isoFormat('DD.MM.YYYY HH:mm') }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Sektion 5: Kommunikation --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.megaphone class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Kommunikation') }}</flux:heading>
                    </div>

                    @if ($cockpit->communicationTemplates->isEmpty())
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine Vorlagen hinterlegt.') }}
                        </flux:text>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-test="cockpit-communication">
                            @foreach ($cockpit->communicationTemplates as $tpl)
                                <div class="flex flex-col rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                                    <div class="flex flex-wrap gap-1.5">
                                        <flux:badge color="indigo" size="sm">
                                            {{ $tpl->audience?->label() ?? '—' }}
                                        </flux:badge>
                                        <flux:badge color="zinc" size="sm" :icon="$tpl->channel?->icon()">
                                            {{ $tpl->channel?->label() ?? '—' }}
                                        </flux:badge>
                                    </div>
                                    @if ($tpl->subject)
                                        <div class="mt-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $tpl->subject }}
                                        </div>
                                    @else
                                        <div class="mt-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $tpl->name }}
                                        </div>
                                    @endif
                                    <p class="mt-2 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $tpl->body }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <flux:button type="button" size="xs" variant="primary" icon="document-text"
                                            wire:click="openTemplate('{{ $tpl->id }}')">
                                            {{ __('Vorlage öffnen') }}
                                        </flux:button>
                                        @if ($tpl->channel === CommunicationChannel::Sms)
                                            <flux:button type="button" size="xs" variant="ghost" icon="device-phone-mobile"
                                                :href="route('communication-templates.index')" wire:navigate>
                                                {{ __('Per SMS senden') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sektion 6: Meldepflichten --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:icon.shield-exclamation class="h-5 w-5 text-zinc-500" />
                        <flux:heading size="lg">{{ __('Meldepflichten') }}</flux:heading>
                    </div>

                    @if (count($cockpit->obligations) === 0)
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Keine offenen Meldepflichten') }}
                        </flux:text>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800" data-test="cockpit-obligations">
                            @foreach ($cockpit->obligations as $entry)
                                @php
                                    $deadline = $entry['deadline_at'] ?? null;
                                    $deadlineIso = $deadline?->toIso8601String();
                                    $reported = (bool) ($entry['reported'] ?? false);
                                    $missed = $deadline !== null && $deadline->isPast() && ! $reported;
                                @endphp
                                <li class="flex items-start justify-between gap-3 py-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $entry['label'] ?? '—' }}
                                        </div>
                                        @if ($deadline)
                                            <div
                                                class="mt-1 text-xs"
                                                x-data="{
                                                    deadline: @js($deadlineIso),
                                                    display: '–',
                                                    tick() {
                                                        if (! this.deadline) { this.display = '–'; return; }
                                                        const target = new Date(this.deadline).getTime();
                                                        let diff = Math.floor((target - Date.now()) / 1000);
                                                        const sign = diff < 0 ? '-' : '';
                                                        diff = Math.abs(diff);
                                                        const h = Math.floor(diff / 3600);
                                                        const m = Math.floor((diff % 3600) / 60);
                                                        const pad = (n) => n.toString().padStart(2, '0');
                                                        this.display = `${sign}${pad(h)}:${pad(m)}`;
                                                    }
                                                }"
                                                x-init="tick(); setInterval(() => tick(), 30000)"
                                            >
                                                <span class="font-semibold">{{ __('Frist:') }}</span>
                                                <span class="font-mono tabular-nums" x-text="display">–</span>
                                                <span class="text-zinc-400">·</span>
                                                <span>{{ $deadline->isoFormat('DD.MM.YYYY HH:mm') }}</span>
                                            </div>
                                        @endif
                                        @if ($missed)
                                            <div class="mt-1 text-xs font-semibold text-rose-700 dark:text-rose-300">
                                                {{ __('Frist abgelaufen — bitte sofort melden!') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="shrink-0">
                                        @if ($reported)
                                            <flux:badge color="emerald" size="sm" icon="check-circle">
                                                {{ __('Gemeldet') }}
                                            </flux:badge>
                                        @else
                                            <flux:badge color="{{ $missed ? 'red' : 'amber' }}" size="sm">
                                                {{ __('Offen') }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <flux:modal name="cockpit-template-preview" class="max-w-2xl">
                @php
                    $preview = $this->previewTemplate;
                @endphp
                @if ($preview)
                    <div class="space-y-5" x-data="{ copied: false, copy(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                        <div>
                            <flux:heading size="lg">{{ $preview->subject ?: $preview->name }}</flux:heading>
                            <flux:subheading>
                                {{ $preview->audience?->label() }} · {{ $preview->channel?->label() }}
                            </flux:subheading>
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                            <pre class="whitespace-pre-wrap font-sans text-zinc-800 dark:text-zinc-100">{{ $preview->body }}</pre>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <flux:button type="button" variant="ghost" icon="clipboard"
                                x-on:click="copy(@js($preview->body))">
                                <span x-show="! copied">{{ __('Kopieren') }}</span>
                                <span x-show="copied" x-cloak>{{ __('Kopiert!') }}</span>
                            </flux:button>
                            <flux:modal.close>
                                <flux:button variant="filled" type="button">{{ __('Schließen') }}</flux:button>
                            </flux:modal.close>
                        </div>
                    </div>
                @endif
            </flux:modal>
        @endif
    @endif
</section>
