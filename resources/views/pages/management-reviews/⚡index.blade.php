<?php

use App\Models\ManagementReview;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Management-Review')] class extends Component {
    public ?string $editingId = null;

    public string $title = '';

    public ?string $review_date = null;

    public string $participants = '';

    public string $summary = '';

    public string $decisions = '';

    public ?string $next_review_at = null;

    public string $conducted_by = '';

    public ?string $deletingId = null;

    public function mount(): void
    {
        $this->conducted_by = (string) (Auth::user()?->name ?? '');
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, ManagementReview>
     */
    #[Computed]
    public function reviews(): Collection
    {
        return ManagementReview::query()
            ->orderByDesc('review_date')
            ->orderByDesc('created_at')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        Flux::modal('review-form')->show();
    }

    public function openEdit(string $id): void
    {
        $review = ManagementReview::findOrFail($id);

        $this->editingId = $review->id;
        $this->title = (string) $review->title;
        $this->review_date = $review->review_date?->toDateString();
        $this->participants = (string) $review->participants;
        $this->summary = (string) $review->summary;
        $this->decisions = (string) $review->decisions;
        $this->next_review_at = $review->next_review_at?->toDateString();
        $this->conducted_by = (string) $review->conducted_by;

        Flux::modal('review-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'review_date' => ['nullable', 'date'],
            'participants' => ['nullable', 'string', 'max:2000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'decisions' => ['nullable', 'string', 'max:5000'],
            'next_review_at' => ['nullable', 'date'],
            'conducted_by' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($this->editingId) {
            ManagementReview::findOrFail($this->editingId)->update($payload);
        } else {
            ManagementReview::create($payload);
        }

        Flux::modal('review-form')->close();
        $this->resetForm();
        unset($this->reviews);

        Flux::toast(variant: 'success', text: __('Management-Review gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('review-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            ManagementReview::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->reviews);
            Flux::modal('review-delete')->close();
            Flux::toast(variant: 'success', text: __('Management-Review gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'review_date', 'participants', 'summary', 'decisions', 'next_review_at']);
        $this->conducted_by = (string) (Auth::user()?->name ?? '');
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Management-Review') }}</flux:heading>
            <flux:subheading>
                {{ __('Dokumentierte Leitungsbewertung des BCMS: Kennzahlen, offene Maßnahmen, Vorfälle und Übungsergebnisse werden bewertet und in Beschlüsse überführt (ISO 22301 §9.3, BSI 200-4 „Aufrechterhaltung“).') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Review') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->reviews as $review)
            <div wire:key="review-{{ $review->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $review->title }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            @if ($review->review_date)
                                <flux:badge color="zinc" size="sm" icon="calendar">{{ $review->review_date->format('d.m.Y') }}</flux:badge>
                            @endif
                            @if ($review->next_review_at)
                                <flux:badge :color="$review->isFollowUpOverdue() ? 'red' : 'zinc'" size="sm" icon="arrow-path">
                                    {{ __('Nächster Review') }}: {{ $review->next_review_at->format('d.m.Y') }}
                                </flux:badge>
                            @endif
                            @if ($review->isFollowUpOverdue())
                                <flux:badge color="red" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $review->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $review->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($review->conducted_by)
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="h-4 w-4 text-zinc-400" />
                            <span>{{ $review->conducted_by }}</span>
                        </div>
                    @endif

                    @if ($review->summary)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($review->summary, 160) }}</flux:text>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Management-Reviews dokumentiert.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="review-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Review bearbeiten') : __('Neuer Management-Review') }}
                </flux:heading>
                <flux:subheading>{{ __('Welche Eingaben wurden bewertet, welche Entscheidungen hat die Leitung getroffen?') }}</flux:subheading>
            </div>

            <flux:input wire:model="title" :label="__('Titel')" type="text" placeholder="z. B. BCMS-Leitungsbewertung 2026/H1" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="review_date" :label="__('Review-Datum')" type="date" />
                <flux:input wire:model="conducted_by" :label="__('Durchführende/r')" type="text" />
            </div>

            <flux:textarea wire:model="participants" :label="__('Teilnehmende')" rows="2" placeholder="z. B. Geschäftsführung, BCM-Beauftragte/r, IT-Leitung" />

            <flux:textarea wire:model="summary" :label="__('Bewertete Eingaben')" rows="4" placeholder="Kennzahlen, offene Maßnahmen, Vorfälle, Übungsergebnisse …" />

            <flux:textarea wire:model="decisions" :label="__('Beschlüsse & Maßnahmen')" rows="4" placeholder="Entscheidungen der Leitung, abgeleitete Maßnahmen, Verbesserungen (KVP) …" />

            <flux:input wire:model="next_review_at" :label="__('Nächster Review')" type="date" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="review-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Review löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="button" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
