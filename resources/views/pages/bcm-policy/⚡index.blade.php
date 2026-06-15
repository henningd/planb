<?php

use App\Enums\BcmPolicyStatus;
use App\Models\BcmPolicy;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('BCM-Leitlinie')] class extends Component {
    public string $scope = '';

    public string $content = '';

    public string $version = '1.0';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * Singleton-Charakter: die aktuellste Leitlinie der Company (oder null).
     */
    #[Computed]
    public function policy(): ?BcmPolicy
    {
        return BcmPolicy::query()
            ->latest('updated_at')
            ->first();
    }

    public function mount(): void
    {
        if (($policy = $this->policy) !== null) {
            $this->scope = (string) $policy->scope;
            $this->content = (string) $policy->content;
            $this->version = (string) $policy->version;
        }
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'scope' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string', 'max:20000'],
            'version' => ['required', 'string', 'max:50'],
        ]);

        if (($policy = $this->policy) !== null) {
            $policy->update($validated);
        } else {
            BcmPolicy::create($validated);
        }

        unset($this->policy);

        Flux::toast(variant: 'success', text: __('BCM-Leitlinie gespeichert.'));
    }

    public function approve(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $policy = $this->policy;

        if ($policy === null) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst eine Leitlinie an.'));

            return;
        }

        $today = CarbonImmutable::now();

        $policy->update([
            'status' => BcmPolicyStatus::Approved,
            'approved_by' => Auth::user()->name,
            'approved_at' => $today->toDateString(),
            'review_due_at' => $today->copy()->addYear()->toDateString(),
        ]);

        unset($this->policy);

        Flux::toast(variant: 'success', text: __('BCM-Leitlinie freigegeben.'));
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('BCM-Leitlinie') }}</flux:heading>
            <flux:subheading>
                {{ __('Leitlinie zum Business Continuity Management mit Geltungsbereich und Freigabe durch die Leitung (NIS2 Art. 20/21, BSI 200-4).') }}
            </flux:subheading>
        </div>

        @if ($this->policy)
            <flux:badge :color="$this->policy->status->color()" size="lg">
                {{ $this->policy->status->label() }}
            </flux:badge>
        @endif
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        @unless ($this->policy)
            <div class="mb-6 rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Es wurde noch keine BCM-Leitlinie hinterlegt.') }}
                </flux:text>
            </div>
        @endunless

        @if ($this->policy)
            <div class="mb-6 grid gap-3 rounded-xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                @if ($this->policy->isApproved())
                    <div class="flex items-center gap-2">
                        <flux:icon.check-badge class="h-4 w-4 text-emerald-500" />
                        <span>
                            {{ __('Freigegeben von') }} <strong>{{ $this->policy->approved_by }}</strong>
                            {{ __('am') }} {{ $this->policy->approved_at?->format('d.m.Y') }}
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:icon.clock class="h-4 w-4" />
                        <span>{{ __('Noch nicht durch die Leitung freigegeben.') }}</span>
                    </div>
                @endif

                @if ($this->policy->review_due_at)
                    <div class="flex items-center gap-2">
                        <flux:icon.calendar class="h-4 w-4 text-zinc-400" />
                        <span @class(['text-red-600 dark:text-red-400 font-medium' => $this->policy->isReviewOverdue()])>
                            {{ __('Nächster Review') }}: {{ $this->policy->review_due_at->format('d.m.Y') }}
                        </span>
                    </div>
                @endif

                @if ($this->policy->isReviewOverdue())
                    <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
                        {{ __('Der Review-Zyklus ist überfällig. Bitte überprüfen und erneut freigeben.') }}
                    </div>
                @endif
            </div>
        @endif

        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $this->policy ? __('Leitlinie bearbeiten') : __('Leitlinie anlegen') }}
                </flux:heading>
                <flux:subheading>{{ __('Geltungsbereich, Inhalt und Versionsstand der BCM-Leitlinie.') }}</flux:subheading>
            </div>

            <flux:textarea wire:model="scope" :label="__('Geltungsbereich')" rows="3" placeholder="Für welche Organisationseinheiten, Standorte und Prozesse gilt diese Leitlinie?" />

            <flux:textarea wire:model="content" :label="__('Inhalt der Leitlinie')" rows="14" placeholder="Ziele, Grundsätze, Verantwortlichkeiten und Geltung des Business Continuity Managements." />

            <flux:input wire:model="version" :label="__('Version')" type="text" placeholder="z. B. 1.0" required />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($this->policy)
                    <flux:button variant="primary" type="button" icon="check-badge" wire:click="approve">
                        {{ __('Freigeben') }}
                    </flux:button>
                @endif
                <flux:button variant="primary" type="submit">
                    {{ $this->policy ? __('Speichern') : __('Leitlinie anlegen') }}
                </flux:button>
            </div>
        </form>
    @endif
</section>
