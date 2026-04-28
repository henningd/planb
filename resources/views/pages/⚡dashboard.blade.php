<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Support\Accessibility\SeverityIndicator;
use App\Support\DashboardActions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()->currentCompany();
    }

    public function confirmReview(): void
    {
        $company = $this->company;
        if (! $company) {
            return;
        }

        $company->forceFill([
            'last_reviewed_at' => now(),
            'last_reminder_sent_at' => null,
        ])->save();

        unset($this->company);

        Flux::toast(variant: 'success', text: __('Review bestätigt. Nächste Prüfung in :n Monaten.', ['n' => $company->review_cycle_months]));
    }

    #[Computed]
    public function employeeCount(): int
    {
        return $this->company ? Employee::count() : 0;
    }

    #[Computed]
    public function primaryContact(): ?Employee
    {
        return $this->company?->primaryContact();
    }

    #[Computed]
    public function emergencyLevelCount(): int
    {
        return $this->company ? EmergencyLevel::count() : 0;
    }

    #[Computed]
    public function crisisRoleHolders()
    {
        if (! $this->company) {
            return collect();
        }

        $order = [
            CrisisRole::Management->value => 1,
            CrisisRole::EmergencyOfficer->value => 2,
            CrisisRole::ItLead->value => 3,
            CrisisRole::DataProtectionOfficer->value => 4,
            CrisisRole::CommunicationsLead->value => 5,
        ];

        return Employee::query()
            ->whereNotNull('crisis_role')
            ->get()
            ->sortBy(fn (Employee $e) => sprintf(
                '%d-%d',
                $order[$e->crisis_role->value] ?? 99,
                $e->is_crisis_deputy ? 1 : 0,
            ))
            ->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function dueItems(): array
    {
        if (! $this->company) {
            return [];
        }

        return DashboardActions::for($this->company);
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

    {{-- Was muss ich heute tun? --}}
    @if ($this->company)
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-2 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:icon.clipboard-document-check class="h-5 w-5 text-zinc-500" />
                <flux:heading size="base">{{ __('Was muss ich heute tun?') }}</flux:heading>
                @if (count($this->dueItems) > 0)
                    <flux:badge color="zinc" size="sm">{{ count($this->dueItems) }}</flux:badge>
                @endif
            </div>
            @if (count($this->dueItems) === 0)
                <div class="flex items-center gap-3 px-5 py-6">
                    <flux:icon.check-circle class="h-5 w-5 text-emerald-500" />
                    <flux:text class="text-zinc-600 dark:text-zinc-300">
                        {{ __('Alles im grünen Bereich.') }}
                    </flux:text>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->dueItems as $item)
                        @php($severity = $item['severity'])
                        @php($badgeColor = match ($severity) {
                            'overdue' => 'rose',
                            'today' => 'amber',
                            'soon' => 'yellow',
                            'active' => 'rose',
                            default => 'zinc',
                        })
                        @php($severityLabel = match ($severity) {
                            'overdue' => __('Überfällig'),
                            'today' => __('Heute'),
                            'soon' => __('Demnächst'),
                            'active' => __('Aktive Lage'),
                            default => '',
                        })
                        @php($severityIcon = SeverityIndicator::dashboardSeverityIcon($severity))
                        <a
                            href="{{ route($item['route'], $item['route_params']) }}"
                            wire:navigate
                            class="flex items-start gap-3 px-5 py-3 text-zinc-900 no-underline hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800"
                        >
                            <div class="mt-1 flex shrink-0 items-center gap-2" data-severity-icon="{{ $severityIcon }}">
                                @if ($severity === 'active')
                                    <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                    </span>
                                @endif
                                <flux:badge :color="$badgeColor" size="sm" :icon="$severityIcon">{{ $severityLabel }}</flux:badge>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium">{{ $item['label'] }}</div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $item['subtitle'] }}
                                </flux:text>
                            </div>
                            <flux:icon.chevron-right class="mt-1 h-4 w-4 shrink-0 text-zinc-400" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Review reminder --}}
    @if ($this->company)
        @php($reviewDue = $this->company->reviewDueAt())
        @if ($reviewDue)
            @php($isOverdue = $reviewDue->isPast())
            @php($isSoon = ! $isOverdue && $reviewDue->diffInDays(now()) <= 14)
            @if ($isOverdue || $isSoon)
                <div class="rounded-xl border-2 p-5 {{ $isOverdue ? 'border-amber-400 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/50' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <flux:icon.clock class="mt-0.5 h-6 w-6 shrink-0 {{ $isOverdue ? 'text-amber-600 dark:text-amber-300' : 'text-zinc-500' }}" />
                            <div>
                                <flux:heading size="base">
                                    @if ($isOverdue)
                                        {{ __('Review überfällig') }}
                                    @else
                                        {{ __('Review fällig am :date', ['date' => $reviewDue->format('d.m.Y')]) }}
                                    @endif
                                </flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    @if ($this->company->last_reviewed_at)
                                        {{ __('Letzte Bestätigung: :date', ['date' => $this->company->last_reviewed_at->format('d.m.Y')]) }}
                                    @else
                                        {{ __('Noch nie bestätigt.') }}
                                    @endif
                                    · {{ __('Prüfzyklus: :n Monate', ['n' => $this->company->review_cycle_months]) }}
                                </flux:text>
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ __('Prüfen Sie Ansprechpartner, Systeme und Dienstleister und bestätigen Sie den aktuellen Stand.') }}
                                </flux:text>
                            </div>
                        </div>
                        <flux:button variant="primary" icon="check" wire:click="confirmReview">
                            {{ __('Jetzt bestätigen') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        @endif
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
                        {{ __('Legen Sie zuerst die Basisdaten Ihres Unternehmens an. Danach können Sie Mitarbeiter, Krisenrollen und Eskalationsstufen pflegen.') }}
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
                        {{ __('Noch keine Geschäftsführung als Krisenrolle hinterlegt') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-amber-900/80 dark:text-amber-200/80">
                        {{ __('Im Ernstfall muss klar sein, wer entscheidet. Weisen Sie einem Mitarbeiter die Krisenrolle „Geschäftsführung" zu.') }}
                    </flux:text>
                </div>
                <flux:button variant="primary" :href="route('employees.index')" wire:navigate>
                    {{ __('Mitarbeiter') }}
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
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mitarbeiter') }}</flux:text>
                <flux:icon.user-group class="h-5 w-5 text-zinc-400" />
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-semibold">{{ $this->employeeCount }}</span>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('angelegt') }}</flux:text>
            </div>
            @if ($this->primaryContact)
                <div class="mt-2 flex items-center gap-2 text-sm">
                    <flux:badge color="emerald" size="sm">{{ __('Hauptansprechpartner') }}</flux:badge>
                    <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $this->primaryContact->fullName() }}</span>
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

    {{-- Two columns: crisis roles + quick actions --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Krisenrollen') }}</flux:heading>
                <flux:link :href="route('employees.index')" wire:navigate class="text-sm">
                    {{ __('Alle Mitarbeiter') }}
                </flux:link>
            </div>
            @forelse ($this->crisisRoleHolders as $employee)
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:avatar :name="$employee->fullName()" size="sm" />
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $employee->fullName() }}</span>
                                @if ($employee->is_crisis_deputy)
                                    <flux:badge color="zinc" size="sm">{{ __('Vertretung') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $employee->position ?: '—' }}
                            </flux:text>
                        </div>
                    </div>
                    <span data-severity-icon="shield-exclamation">
                        <flux:badge color="red" size="sm" icon="shield-exclamation">{{ $employee->crisis_role->label() }}</flux:badge>
                    </span>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Krisenrollen vergeben.') }}
                    </flux:text>
                </div>
            @endforelse
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Schnellzugriff') }}</flux:heading>
            </div>
            <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
                <a href="{{ route('company.edit') }}" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 no-underline hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.building-office-2 class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Firmenprofil') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </a>
                <a href="{{ route('employees.index') }}" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 no-underline hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.user-group class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Mitarbeiter') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </a>
                <a href="{{ route('emergency-levels.index') }}" wire:navigate class="flex items-center gap-3 px-5 py-4 text-zinc-900 no-underline hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon.shield-exclamation class="h-5 w-5 text-zinc-500" />
                    <span class="flex-1 font-medium">{{ __('Notfall-Level') }}</span>
                    <flux:icon.chevron-right class="h-4 w-4 text-zinc-400" />
                </a>
                @if ($this->company)
                    <a href="{{ route('handbook.print') }}" target="_blank" class="flex items-center gap-3 px-5 py-4 text-zinc-900 no-underline hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        <flux:icon.document-arrow-down class="h-5 w-5 text-zinc-500" />
                        <span class="flex-1 font-medium">{{ __('Handbuch als PDF') }}</span>
                        <flux:icon.arrow-top-right-on-square class="h-4 w-4 text-zinc-400" />
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
