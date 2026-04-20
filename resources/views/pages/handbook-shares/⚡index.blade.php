<?php

use App\Models\HandbookShare;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Freigabelinks')] class extends Component {
    public string $label = '';

    public int $validDays = 14;

    public ?string $revokingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, HandbookShare>
     */
    #[Computed]
    public function shares(): Collection
    {
        return HandbookShare::with('createdBy')->get();
    }

    public function create(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'label' => ['required', 'string', 'max:120'],
            'validDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        HandbookShare::create([
            'token' => HandbookShare::generateToken(),
            'label' => $validated['label'],
            'expires_at' => Carbon::now()->addDays($validated['validDays']),
            'created_by_user_id' => Auth::id(),
        ]);

        $this->reset(['label']);
        $this->validDays = 14;
        unset($this->shares);

        Flux::toast(variant: 'success', text: __('Freigabelink erstellt.'));
    }

    public function confirmRevoke(string $id): void
    {
        $this->revokingId = $id;
        Flux::modal('share-revoke')->show();
    }

    public function revoke(): void
    {
        if ($this->revokingId) {
            HandbookShare::findOrFail($this->revokingId)->update(['revoked_at' => now()]);
            $this->revokingId = null;
            unset($this->shares);
            Flux::modal('share-revoke')->close();
            Flux::toast(variant: 'success', text: __('Freigabe widerrufen.'));
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Read-only-Freigabelinks') }}</flux:heading>
        <flux:subheading>
            {{ __('Geben Sie das Notfallhandbuch zeitlich befristet für Versicherung, Auditor oder IT-Berater frei – ohne Zugangsdaten.') }}
        </flux:subheading>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="base">{{ __('Neuen Link erstellen') }}</flux:heading>
        <form wire:submit="create" class="mt-4 grid gap-4 md:grid-cols-[2fr_1fr_auto] md:items-end">
            <flux:input
                wire:model="label"
                :label="__('Verwendungszweck')"
                required
                placeholder="z. B. Auditor Musterprüfer GmbH"
            />
            <flux:field>
                <flux:label>{{ __('Gültig (Tage)') }}</flux:label>
                <flux:input wire:model="validDays" type="number" min="1" max="365" required />
            </flux:field>
            <flux:button type="submit" variant="primary" icon="plus" :disabled="! $this->hasCompany">
                {{ __('Link erstellen') }}
            </flux:button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($this->shares as $share)
            @php($status = $share->status())
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ $share->label }}</span>
                        @if ($status === 'active')
                            <flux:badge color="emerald" size="sm">{{ __('Aktiv') }}</flux:badge>
                        @elseif ($status === 'expired')
                            <flux:badge color="zinc" size="sm">{{ __('Abgelaufen') }}</flux:badge>
                        @else
                            <flux:badge color="rose" size="sm">{{ __('Widerrufen') }}</flux:badge>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Gültig bis') }}: {{ $share->expires_at->format('d.m.Y H:i') }}
                        @if ($share->createdBy) · {{ __('angelegt von') }} {{ $share->createdBy->name }} @endif
                        · {{ __(':n Aufrufe', ['n' => $share->access_count]) }}
                        @if ($share->last_accessed_at)
                            · {{ __('letzter Zugriff') }} {{ $share->last_accessed_at->diffForHumans() }}
                        @endif
                    </flux:text>
                    @if ($status === 'active')
                        <div class="mt-3 flex items-center gap-2">
                            <flux:input
                                size="sm"
                                readonly
                                :value="route('handbook.shared', $share->token)"
                                class="w-full font-mono text-xs"
                                x-data
                                x-on:click="$el.select()"
                            />
                            <flux:button
                                size="sm"
                                variant="filled"
                                icon="clipboard"
                                x-data
                                x-on:click="navigator.clipboard.writeText('{{ route('handbook.shared', $share->token) }}')"
                            >
                                {{ __('Kopieren') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
                @if ($status === 'active')
                    <flux:button size="sm" variant="danger" icon="x-mark" wire:click="confirmRevoke('{{ $share->id }}')">
                        {{ __('Widerrufen') }}
                    </flux:button>
                @endif
            </div>
        @empty
            <div class="px-5 py-12 text-center">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Freigabelinks angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="share-revoke" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Link widerrufen?') }}</flux:heading>
                <flux:subheading>{{ __('Der Link ist sofort ungültig und kann nicht reaktiviert werden. Erstellen Sie bei Bedarf einen neuen.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="revoke">{{ __('Widerrufen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
