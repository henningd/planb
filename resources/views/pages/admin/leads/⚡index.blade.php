<?php

use App\Models\Lead;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Admin · Leads')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?string $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{total: int, confirmed: int, marketing: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Lead::count(),
            'confirmed' => Lead::confirmed()->count(),
            'marketing' => Lead::where('consent_marketing', true)->whereNotNull('confirmed_at')->count(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Lead>
     */
    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        return Lead::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('email', 'like', $term)
                        ->orWhere('company_name', 'like', $term)
                        ->orWhere('contact_name', 'like', $term);
                });
            })
            ->when($this->status === 'confirmed', fn ($query) => $query->whereNotNull('confirmed_at'))
            ->when($this->status === 'pending', fn ($query) => $query->whereNull('confirmed_at'))
            ->latest()
            ->paginate(25);
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('admin-lead-delete')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        Lead::whereKey($this->deletingId)->delete();

        $this->deletingId = null;
        unset($this->leads, $this->stats);
        Flux::modal('admin-lead-delete')->close();
        Flux::toast(variant: 'success', text: __('Lead gelöscht.'));
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Personenbezogene Kontaktdaten. Bitte nur zweckgebunden verarbeiten (Art. 6 DSGVO).') }}
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('NIS2 Quick-Check Leads') }}</flux:heading>
        <flux:subheading>{{ __('Über den öffentlichen Quick-Check eingesammelte Interessenten.') }}</flux:subheading>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Leads gesamt') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['total'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Bestätigt (Double-Opt-In)') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['confirmed'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Newsletter-Einwilligung') }}</flux:text>
            <div class="mt-2 text-3xl font-semibold">{{ $this->stats['marketing'] }}</div>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Suche')" placeholder="{{ __('E-Mail, Firma, Name') }}" class="max-w-xs" />
        <flux:select wire:model.live="status" :label="__('Status')" class="max-w-[12rem]">
            <flux:select.option value="">{{ __('Alle') }}</flux:select.option>
            <flux:select.option value="confirmed">{{ __('Bestätigt') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Unbestätigt') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                <tr>
                    <th class="px-5 py-3">{{ __('Datum') }}</th>
                    <th class="px-5 py-3">{{ __('Kontakt') }}</th>
                    <th class="px-5 py-3">{{ __('Reifegrad') }}</th>
                    <th class="px-5 py-3">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-center">{{ __('Newsletter') }}</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->leads as $lead)
                    <tr wire:key="lead-{{ $lead->id }}">
                        <td class="px-5 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            {{ $lead->created_at?->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $lead->email }}</div>
                            @if ($lead->company_name || $lead->contact_name)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ collect([$lead->contact_name, $lead->company_name])->filter()->implode(' · ') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if ($lead->readiness)
                                <flux:badge :color="$lead->readiness->color()" size="sm">{{ $lead->readiness->label() }}</flux:badge>
                                <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400 tabular-nums">{{ $lead->score }}/{{ \App\Support\Marketing\Nis2QuickCheckCatalog::maxScore() }}</span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if ($lead->isConfirmed())
                                <flux:badge color="emerald" size="sm">{{ __('Bestätigt') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Offen') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if ($lead->consent_marketing)
                                <flux:icon.check class="mx-auto h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">–</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $lead->id }}')" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Leads erfasst.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->leads->hasPages())
        <div class="mt-4">
            {{ $this->leads->onEachSide(1)->links() }}
        </div>
    @endif

    <flux:modal name="admin-lead-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Lead löschen?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Der Datensatz wird unwiderruflich entfernt – z. B. zur Erfüllung eines Löschverlangens nach Art. 17 DSGVO.') }}
                </flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
