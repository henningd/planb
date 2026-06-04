<?php

use App\Models\AuthActivity;
use App\Support\AuthActivityFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Anmeldungen')] class extends Component {
    use WithPagination;

    public string $event = '';

    public string $search = '';

    public string $from = '';

    public string $to = '';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * Aktuelle Filter als Query-Parameter, wie sie der Export-Controller erwartet.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function exportQuery(): array
    {
        return [
            'event' => $this->event,
            'search' => $this->search,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    /**
     * @return LengthAwarePaginator<AuthActivity>
     */
    public function entries(): LengthAwarePaginator
    {
        return AuthActivityFilter::build([
            'event' => $this->event,
            'search' => $this->search,
            'from' => $this->from,
            'to' => $this->to,
        ])->paginate(25);
    }

    public function resetFilters(): void
    {
        $this->reset(['event', 'search', 'from', 'to']);
        $this->resetPage();
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{color: string, label: string, icon: string}
     */
    public function eventBadge(string $event): array
    {
        return match ($event) {
            'login' => ['color' => 'emerald', 'label' => __('Angemeldet'), 'icon' => 'arrow-right-end-on-rectangle'],
            'logout' => ['color' => 'zinc', 'label' => __('Abgemeldet'), 'icon' => 'arrow-left-start-on-rectangle'],
            'failed' => ['color' => 'rose', 'label' => __('Fehlgeschlagen'), 'icon' => 'exclamation-triangle'],
            default => ['color' => 'sky', 'label' => $event, 'icon' => 'finger-print'],
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Anmeldungen') }}</flux:heading>
        <flux:subheading>
            {{ __('Wer hat sich wann an- und abgemeldet. Fehlgeschlagene Versuche inklusive.') }}
        </flux:subheading>
    </div>

    <flux:navbar class="mb-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:navbar.item :href="route('audit-log.index')" :current="request()->routeIs('audit-log.*')" wire:navigate>
            {{ __('Änderungen') }}
        </flux:navbar.item>
        <flux:navbar.item :href="route('login-activity.index')" :current="request()->routeIs('login-activity.*')" wire:navigate>
            {{ __('Anmeldungen') }}
        </flux:navbar.item>
    </flux:navbar>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <flux:field>
                <flux:label>{{ __('Ereignis') }}</flux:label>
                <flux:select wire:model.live="event">
                    <flux:select.option value="">{{ __('Alle') }}</flux:select.option>
                    <flux:select.option value="login">{{ __('Angemeldet') }}</flux:select.option>
                    <flux:select.option value="logout">{{ __('Abgemeldet') }}</flux:select.option>
                    <flux:select.option value="failed">{{ __('Fehlgeschlagen') }}</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Von') }}</flux:label>
                <flux:input type="date" wire:model.live="from" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Bis') }}</flux:label>
                <flux:input type="date" wire:model.live="to" />
            </flux:field>
            <flux:field class="min-w-64">
                <flux:label>{{ __('Suche') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Name, E-Mail oder IP') }}" />
            </flux:field>
            @if ($event !== '' || $search !== '' || $from !== '' || $to !== '')
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="resetFilters">
                    {{ __('Filter zurücksetzen') }}
                </flux:button>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="arrow-down-tray"
                    :href="route('login-activity.export.csv', $this->exportQuery)"
                >
                    {{ __('Als CSV') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="document-text"
                    :href="route('login-activity.export.pdf', $this->exportQuery)"
                >
                    {{ __('Als PDF') }}
                </flux:button>
            </div>
        </div>

        @php($entries = $this->entries())

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($entries as $entry)
                @php($badge = $this->eventBadge($entry->event))
                <div class="border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm" :color="$badge['color']" :icon="$badge['icon']">
                            {{ $badge['label'] }}
                        </flux:badge>
                        <span class="font-medium">{{ $entry->user?->name ?? $entry->email ?? __('Unbekannt') }}</span>
                        @if ($entry->email && $entry->user)
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $entry->email }}</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                        <span>{{ $entry->created_at->format('d.m.Y H:i:s') }}</span>
                        @if ($entry->ip_address)
                            <span class="font-mono text-xs">{{ $entry->ip_address }}</span>
                        @endif
                        @if ($entry->user_agent)
                            <span class="truncate max-w-md" title="{{ $entry->user_agent }}">{{ $entry->user_agent }}</span>
                        @endif
                    </flux:text>
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
