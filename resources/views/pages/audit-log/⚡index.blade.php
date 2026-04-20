<?php

use App\Models\AuditLogEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Aktivitäten')] class extends Component {
    use WithPagination;

    public string $entityType = '';

    public string $action = '';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return LengthAwarePaginator<AuditLogEntry>
     */
    public function entries(): LengthAwarePaginator
    {
        $query = AuditLogEntry::with('user')->orderByDesc('created_at');

        if ($this->entityType !== '') {
            $query->where('entity_type', $this->entityType);
        }

        if ($this->action !== '') {
            $query->where('action', $this->action);
        }

        return $query->paginate(25);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function entityTypes(): array
    {
        return AuditLogEntry::query()
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type')
            ->all();
    }

    public function resetFilters(): void
    {
        $this->reset(['entityType', 'action']);
        $this->resetPage();
    }

    public function updatingEntityType(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Aktivitätsprotokoll') }}</flux:heading>
        <flux:subheading>
            {{ __('Wer hat wann was geändert. Für Audits und Prüfer nachvollziehbar.') }}
        </flux:subheading>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <flux:field>
                <flux:label>{{ __('Objekttyp') }}</flux:label>
                <flux:select wire:model.live="entityType">
                    <flux:select.option value="">{{ __('Alle') }}</flux:select.option>
                    @foreach ($this->entityTypes as $type)
                        <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Aktion') }}</flux:label>
                <flux:select wire:model.live="action">
                    <flux:select.option value="">{{ __('Alle') }}</flux:select.option>
                    <flux:select.option value="created">{{ __('Angelegt') }}</flux:select.option>
                    <flux:select.option value="updated">{{ __('Geändert') }}</flux:select.option>
                    <flux:select.option value="deleted">{{ __('Gelöscht') }}</flux:select.option>
                </flux:select>
            </flux:field>
            @if ($entityType !== '' || $action !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                    {{ __('Filter zurücksetzen') }}
                </flux:button>
            @endif
        </div>

        @php($entries = $this->entries())

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($entries as $entry)
                <div class="border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge
                                    size="sm"
                                    :color="match ($entry->action) { 'created' => 'emerald', 'deleted' => 'rose', default => 'sky' }"
                                >
                                    @switch ($entry->action)
                                        @case('created') {{ __('Angelegt') }} @break
                                        @case('deleted') {{ __('Gelöscht') }} @break
                                        @default {{ __('Geändert') }}
                                    @endswitch
                                </flux:badge>
                                <flux:badge color="zinc" size="sm">{{ $entry->entity_type }}</flux:badge>
                                <span class="font-medium">{{ $entry->entity_label ?? $entry->entity_id }}</span>
                            </div>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $entry->created_at->format('d.m.Y H:i') }}
                                @if ($entry->user) · {{ $entry->user->name }} @endif
                            </flux:text>

                            @if ($entry->action === 'updated' && is_array($entry->changes) && $entry->changes !== [])
                                <div class="mt-2 space-y-1 text-sm">
                                    @foreach ($entry->changes as $field => $diff)
                                        <div class="flex flex-wrap items-baseline gap-2 text-zinc-700 dark:text-zinc-200">
                                            <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $field }}</span>
                                            <span class="text-zinc-500 line-through">{{ \Illuminate\Support\Str::limit((string) ($diff['old'] ?? ''), 120) ?: '—' }}</span>
                                            <span class="text-zinc-400">→</span>
                                            <span>{{ \Illuminate\Support\Str::limit((string) ($diff['new'] ?? ''), 120) ?: '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($entry->action === 'created' && is_array($entry->changes) && $entry->changes !== [])
                                <div class="mt-2 flex flex-wrap items-baseline gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Felder') }}: {{ implode(', ', array_keys($entry->changes)) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('Keine Einträge.') }}
                    </flux:text>
                </div>
            @endforelse
        </div>

        @if ($entries->hasPages())
            <div class="mt-4">
                {{ $entries->onEachSide(1)->links() }}
            </div>
        @endif
    @endunless
</section>
