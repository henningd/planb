<?php

use App\Models\Contract;
use App\Support\Duration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vertrag')] class extends Component {
    public Contract $contract;

    public function mount(Contract $contract): void
    {
        abort_if($contract->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->contract = $contract->load(['serviceProvider', 'systems', 'locations']);
    }

    public function deleteContract(): void
    {
        $this->contract->delete();
        $this->redirectRoute('contracts.index', navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div>
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('contracts.index')" wire:navigate>
            {{ __('Zurück zu den Verträgen') }}
        </flux:button>

        <div class="mt-2 flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $contract->title }}</flux:heading>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <flux:badge :color="$contract->statusColor()">{{ $contract->statusLabel() }}</flux:badge>
                    @if ($contract->coverage)
                        <flux:badge :color="$contract->coverage->badgeColor()">{{ $contract->coverage->label() }}</flux:badge>
                    @endif
                    @if ($contract->contract_number)
                        <flux:badge color="zinc">{{ $contract->contract_number }}</flux:badge>
                    @endif
                </div>
                @if ($contract->serviceProvider)
                    <flux:text class="mt-2 text-sm">
                        {{ __('Dienstleister:') }}
                        <a href="{{ route('service-providers.show', $contract->serviceProvider) }}" wire:navigate class="font-medium hover:underline">
                            {{ $contract->serviceProvider->name }}
                        </a>
                    </flux:text>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('contracts.edit', $contract)" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="danger"
                    icon="trash"
                    wire:click="deleteContract"
                    wire:confirm="{{ __('Diesen Vertrag wirklich löschen?') }}"
                >
                    {{ __('Löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Störungs-/Notfallkontakt zuerst — das zählt im Ernstfall. --}}
    @php($hotline = $contract->emergency_hotline ?: $contract->serviceProvider?->hotline)
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-6 dark:border-rose-900 dark:bg-rose-950">
        <div class="flex items-center gap-2">
            <flux:icon.phone-arrow-up-right class="h-5 w-5 text-rose-600 dark:text-rose-400" />
            <flux:heading size="md" class="text-rose-900 dark:text-rose-100">{{ __('Im Störungsfall') }}</flux:heading>
        </div>
        <div class="mt-3 grid gap-4 md:grid-cols-3">
            <div>
                <div class="text-xs uppercase text-rose-600 dark:text-rose-400">{{ __('Hotline') }}</div>
                @if ($hotline)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}" class="text-lg font-semibold text-rose-900 hover:underline dark:text-rose-100">
                        {{ $hotline }}
                    </a>
                    @unless ($contract->emergency_hotline)
                        <div class="text-xs text-rose-500 dark:text-rose-400">{{ __('(allgemeine Dienstleister-Hotline)') }}</div>
                    @endunless
                @else
                    <div class="text-zinc-400">—</div>
                @endif
            </div>
            <div>
                <div class="text-xs uppercase text-rose-600 dark:text-rose-400">{{ __('Reaktionszeit') }}</div>
                <div class="text-lg font-semibold text-rose-900 dark:text-rose-100">
                    {{ Duration::format($contract->response_time_minutes) ?? '—' }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase text-rose-600 dark:text-rose-400">{{ __('Ansprechpartner') }}</div>
                <div class="text-sm text-rose-900 dark:text-rose-100">
                    {{ $contract->emergency_contact_name ?: '—' }}
                    @if ($contract->emergency_contact_phone)
                        <div>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contract->emergency_contact_phone) }}" class="hover:underline">
                                {{ $contract->emergency_contact_phone }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('SLA / Servicevereinbarung') }}</flux:heading>
        <dl class="mt-4 grid gap-x-6 gap-y-3 md:grid-cols-2">
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Abdeckung') }}</dt>
                <dd class="font-medium">{{ $contract->coverage?->label() ?? '—' }}</dd>
            </div>
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Servicezeiten') }}</dt>
                <dd class="font-medium">{{ $contract->service_hours ?: '—' }}</dd>
            </div>
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Reaktionszeit') }}</dt>
                <dd class="font-medium">{{ Duration::format($contract->response_time_minutes) ?? '—' }}</dd>
            </div>
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Wiederherstellungszeit') }}</dt>
                <dd class="font-medium">{{ Duration::format($contract->resolution_time_minutes) ?? '—' }}</dd>
            </div>
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Verfügbarkeit') }}</dt>
                <dd class="font-medium">{{ $contract->availability_percent !== null ? rtrim(rtrim(number_format((float) $contract->availability_percent, 2, ',', '.'), '0'), ',').' %' : '—' }}</dd>
            </div>
            <div class="flex justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                <dt class="text-zinc-500">{{ __('Laufzeit') }}</dt>
                <dd class="font-medium">
                    {{ $contract->start_date?->format('d.m.Y') ?? '—' }} – {{ $contract->end_date?->format('d.m.Y') ?? '—' }}
                </dd>
            </div>
        </dl>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Verknüpfte Systeme') }}</flux:heading>
            @if ($contract->systems->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">{{ __('Keine Systeme verknüpft.') }}</flux:text>
            @else
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($contract->systems as $system)
                        <a href="{{ route('systems.show', $system) }}" wire:navigate>
                            <flux:badge color="sky" size="sm">{{ $system->name }}</flux:badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Verknüpfte Standorte') }}</flux:heading>
            @if ($contract->locations->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">{{ __('Keine Standorte verknüpft.') }}</flux:text>
            @else
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($contract->locations as $location)
                        <flux:badge color="violet" size="sm">{{ $location->name }}</flux:badge>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($contract->notes)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Notizen') }}</flux:heading>
            <flux:text class="mt-3 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-400">{{ $contract->notes }}</flux:text>
        </div>
    @endif
</section>
