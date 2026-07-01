<?php

use App\Models\Contract;
use App\Models\ServiceProvider;
use App\Support\Duration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Verträge')] class extends Component {
    public string $search = '';

    public string $provider = '';

    public string $status = '';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function providers()
    {
        return ServiceProvider::orderBy('name')->get();
    }

    #[Computed]
    public function contracts()
    {
        $query = Contract::with(['serviceProvider', 'systems', 'locations'])->orderBy('title');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)->orWhere('contract_number', 'like', $term);
            });
        }

        if ($this->provider !== '') {
            $query->where('service_provider_id', $this->provider);
        }

        $contracts = $query->get();

        if ($this->status !== '') {
            $contracts = $contracts->filter(fn (Contract $c) => $c->status() === $this->status)->values();
        }

        return $contracts;
    }

    /**
     * @return array{total: int, active: int, expiring: int, expired: int}
     */
    #[Computed]
    public function stats(): array
    {
        $stats = ['total' => 0, 'active' => 0, 'expiring' => 0, 'expired' => 0];
        foreach (Contract::all() as $contract) {
            $stats['total']++;
            $stats[$contract->status()]++;
        }

        return $stats;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'provider', 'status']);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Verträge') }}</flux:heading>
            <flux:subheading>
                {{ __('Service- und Wartungsverträge mit SLA, Reaktionszeit und Störungs-Hotline — im Notfall sofort griffbereit.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('contracts.create')" wire:navigate :disabled="! $this->hasCompany">
            {{ __('Neuer Vertrag') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-2xl font-semibold text-zinc-700 dark:text-zinc-300">{{ $this->stats['total'] }}</div>
            <div class="text-xs uppercase text-zinc-600 dark:text-zinc-400">{{ __('Verträge gesamt') }}</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
            <div class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $this->stats['active'] }}</div>
            <div class="text-xs uppercase text-emerald-600 dark:text-emerald-400">{{ __('Aktiv') }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
            <div class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $this->stats['expiring'] }}</div>
            <div class="text-xs uppercase text-amber-600 dark:text-amber-400">{{ __('Läuft bald ab') }}</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950">
            <div class="text-2xl font-semibold text-rose-700 dark:text-rose-300">{{ $this->stats['expired'] }}</div>
            <div class="text-xs uppercase text-rose-600 dark:text-rose-400">{{ __('Abgelaufen') }}</div>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:input wire:model.live.debounce.300ms="search" size="sm" class="w-56" icon="magnifying-glass" :placeholder="__('Titel oder Vertragsnummer')" />

        <flux:select wire:model.live="provider" size="sm" class="w-56">
            <flux:select.option value="">{{ __('Alle Dienstleister') }}</flux:select.option>
            @foreach ($this->providers as $p)
                <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" size="sm" class="w-48">
            <flux:select.option value="">{{ __('Alle Status') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Aktiv') }}</flux:select.option>
            <flux:select.option value="expiring">{{ __('Läuft bald ab') }}</flux:select.option>
            <flux:select.option value="expired">{{ __('Abgelaufen') }}</flux:select.option>
        </flux:select>

        @if ($search || $provider || $status)
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">
                {{ __('Filter zurücksetzen') }}
            </flux:button>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3">{{ __('Vertrag') }}</th>
                    <th class="px-4 py-3">{{ __('Dienstleister') }}</th>
                    <th class="px-4 py-3">{{ __('Abdeckung') }}</th>
                    <th class="px-4 py-3">{{ __('Reaktionszeit') }}</th>
                    <th class="px-4 py-3">{{ __('Verknüpft') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3">{{ __('Laufzeit bis') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                @forelse ($this->contracts as $contract)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('contracts.show', $contract) }}" wire:navigate class="font-medium hover:underline">
                                {{ $contract->title }}
                            </a>
                            @if ($contract->contract_number)
                                <div class="text-xs text-zinc-500">{{ $contract->contract_number }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $contract->serviceProvider?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($contract->coverage)
                                <flux:badge :color="$contract->coverage->badgeColor()" size="sm">{{ $contract->coverage->label() }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ Duration::format($contract->response_time_minutes) ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            @php($sys = $contract->systems->count())
                            @php($loc = $contract->locations->count())
                            @if ($sys || $loc)
                                <span class="text-xs">
                                    @if ($sys){{ trans_choice(':count System|:count Systeme', $sys, ['count' => $sys]) }}@endif
                                    @if ($sys && $loc) · @endif
                                    @if ($loc){{ trans_choice(':count Standort|:count Standorte', $loc, ['count' => $loc]) }}@endif
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$contract->statusColor()" size="sm">{{ $contract->statusLabel() }}</flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $contract->end_date?->format('d.m.Y') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                            {{ __('Keine Verträge gefunden — entweder Filter zu eng oder noch keine erfasst.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
