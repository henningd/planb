<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Abrechnung')] class extends Component {
    public string $billingCycle = 'yearly';

    /**
     * Aktuell ausgewählter Tarif (für UI-Highlight) — leer, bis ausgewählt.
     */
    public ?string $selectedPlan = null;

    /** @var array<string, int> */
    public array $addonQuantities = [];

    public function mount(): void
    {
        $this->selectedPlan = $this->team()?->activePlanKey();

        foreach (array_keys(config('billing.addons')) as $key) {
            $this->addonQuantities[$key] = 1;
        }
    }

    public function team(): ?Team
    {
        return Auth::user()?->currentTeam;
    }

    #[Computed]
    public function plans(): array
    {
        return config('billing.plans');
    }

    #[Computed]
    public function addons(): array
    {
        return config('billing.addons');
    }

    #[Computed]
    public function activePlan(): ?string
    {
        return $this->team()?->activePlanKey();
    }

    #[Computed]
    public function isOnTrial(): bool
    {
        $team = $this->team();

        return $team !== null
            && $team->onGenericTrial();
    }

    #[Computed]
    public function trialEndsAt(): ?string
    {
        return $this->team()?->trial_ends_at?->toFormattedDateString();
    }

    #[Computed]
    public function isFrozen(): bool
    {
        return (bool) $this->team()?->isFrozen();
    }

    #[Computed]
    public function subscription()
    {
        return $this->team()?->subscription('default');
    }

    #[Computed]
    public function isSubscribed(): bool
    {
        return (bool) $this->team()?->subscribed('default');
    }

    #[Computed]
    public function onGracePeriod(): bool
    {
        return $this->subscription()?->onGracePeriod() ?? false;
    }

    /**
     * @return iterable<\Laravel\Cashier\Invoice>
     */
    #[Computed]
    public function invoices(): iterable
    {
        $team = $this->team();

        if ($team === null || $team->stripe_id === null) {
            return [];
        }

        try {
            return $team->invoices();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Stripe-Checkout-Optionen mit Tax/Reverse-Charge gemäß config/billing.php.
     */
    protected function checkoutOptions(string $kind = 'subscription'): array
    {
        $opts = [
            'success_url' => route('billing.edit').'?checkout=success',
            'cancel_url' => route('billing.edit').'?checkout=cancel',
        ];

        if (config('billing.automatic_tax')) {
            $opts['automatic_tax'] = ['enabled' => true];
            // Pflicht, wenn automatic_tax an ist — Stripe synchronisiert
            // Adresse und Name vom Checkout zurück auf den Customer.
            $opts['customer_update'] = ['name' => 'auto', 'address' => 'auto'];
        }

        if (config('billing.tax_id_collection')) {
            $opts['tax_id_collection'] = ['enabled' => true];
        }

        if ($kind === 'payment') {
            $opts['mode'] = 'payment';
        }

        return $opts;
    }

    /**
     * Tarif wechseln oder neu abonnieren.
     */
    public function selectPlan(string $planKey, string $cycle)
    {
        $team = $this->team();
        abort_unless($team !== null, 403);

        $plans = config('billing.plans');
        abort_unless(isset($plans[$planKey]), 404);

        $priceId = $cycle === 'monthly'
            ? $plans[$planKey]['monthly_price_id']
            : $plans[$planKey]['yearly_price_id'];

        if (blank($priceId)) {
            Flux::toast(variant: 'warning', text: __('Für diesen Tarif ist kein Stripe-Preis hinterlegt. Bitte kontaktieren Sie uns.'));

            return null;
        }

        if ($this->isSubscribed && ! $this->onGracePeriod) {
            $team->subscription('default')->swap($priceId);
            Flux::toast(variant: 'success', text: __('Tarif gewechselt — anteilig abgerechnet.'));

            return null;
        }

        // Generic-Trial in Stripe-Trial überführen, falls noch im Test-Zeitraum.
        $builder = $team->newSubscription('default', $priceId);

        if ($team->onGenericTrial()) {
            $builder->trialUntil($team->trial_ends_at);
        }

        $checkout = $builder->checkout($this->checkoutOptions());

        return redirect($checkout->url);
    }

    /**
     * Add-on bestellen — Einmalzahlung oder Abo-Add-on.
     */
    public function purchaseAddon(string $addonKey)
    {
        $team = $this->team();
        abort_unless($team !== null, 403);

        $addons = config('billing.addons');
        abort_unless(isset($addons[$addonKey]), 404);

        $priceId = $addons[$addonKey]['price_id'] ?? null;

        if (blank($priceId)) {
            Flux::toast(variant: 'warning', text: __('Dieser Posten ist noch nicht hinterlegt. Bitte kontaktieren Sie uns.'));

            return null;
        }

        $quantity = max(1, (int) ($this->addonQuantities[$addonKey] ?? 1));

        $checkout = $addons[$addonKey]['mode'] === 'subscription'
            ? $team->newSubscription($addonKey, $priceId)->checkout($this->checkoutOptions())
            : $team->checkout([$priceId => $quantity], $this->checkoutOptions('payment'));

        return redirect($checkout->url);
    }

    /**
     * Abo zum Periodenende kündigen.
     */
    public function cancelSubscription(): void
    {
        $team = $this->team();
        abort_unless($team?->subscribed('default'), 403);

        $team->subscription('default')->cancel();

        Flux::toast(variant: 'success', text: __('Kündigung vorgemerkt — Abo läuft bis zum Periodenende weiter.'));
    }

    /**
     * Kündigung in der Schonfrist zurücknehmen.
     */
    public function resumeSubscription(): void
    {
        $team = $this->team();
        abort_unless($team?->subscription('default')?->onGracePeriod(), 403);

        $team->subscription('default')->resume();

        Flux::toast(variant: 'success', text: __('Abo fortgesetzt.'));
    }

    /**
     * Add-on-Abo (z. B. Coaching-Retainer) zum Periodenende kündigen.
     */
    public function cancelAddon(string $addonKey): void
    {
        $team = $this->team();
        abort_unless($team?->subscribed($addonKey), 403);

        $team->subscription($addonKey)->cancel();

        Flux::toast(variant: 'success', text: __('Add-on-Abo zum Periodenende gekündigt.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Abrechnung') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Abrechnung')" :subheading="__('Tarif, Rechnungen und Zusatzleistungen für diesen Mandanten verwalten')" full-width>

        @if (session('billing.frozen'))
            <flux:callout variant="danger" icon="lock-closed" class="mb-6">
                <flux:callout.heading>{{ __('Konto eingefroren') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Ihr Test-Zeitraum ist abgelaufen. Daten bleiben lesbar und exportierbar — neue Eingaben sind erst nach Buchung eines Tarifs wieder möglich.') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (request('checkout') === 'success' || request('addon') === 'success')
            <flux:callout variant="success" icon="check-circle" class="mb-6">
                <flux:callout.heading>{{ __('Zahlung erfolgreich.') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Die Abwicklung über Stripe ist abgeschlossen. Es kann ein paar Sekunden dauern, bis der Status hier aktualisiert ist.') }}</flux:callout.text>
            </flux:callout>
        @endif

        {{-- ============ STATUS-BLOCK ============ --}}
        <div class="mb-8 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Aktueller Status') }}</flux:heading>

            <div class="mt-4 space-y-2 text-sm">
                @if ($this->isFrozen)
                    <flux:badge color="rose">{{ __('Eingefroren') }}</flux:badge>
                    <flux:text>{{ __('Test-Zeitraum abgelaufen, kein gültiges Abo. Schreibvorgänge sind gesperrt.') }}</flux:text>
                @elseif ($this->isOnTrial)
                    <flux:badge color="amber">{{ __('Test-Zeitraum') }}</flux:badge>
                    <flux:text>
                        {{ __('Sie testen ":plan" noch bis zum :date.', ['plan' => $this->plans[config('billing.trial_plan')]['name'] ?? 'Advanced', 'date' => $this->trialEndsAt]) }}
                    </flux:text>
                    <flux:text variant="subtle">{{ __('Wählen Sie unten einen Tarif, um nach Ablauf weiter Zugriff zu behalten.') }}</flux:text>
                @elseif ($this->onGracePeriod)
                    <flux:badge color="amber">{{ __('Gekündigt') }}</flux:badge>
                    <flux:text>
                        {{ __('Aktueller Tarif: :plan. Läuft am :date aus.', [
                            'plan' => $this->plans[$this->activePlan]['name'] ?? '–',
                            'date' => $this->subscription?->ends_at?->toFormattedDateString(),
                        ]) }}
                    </flux:text>
                    <flux:button wire:click="resumeSubscription" size="sm" variant="primary" type="button">
                        {{ __('Kündigung zurücknehmen') }}
                    </flux:button>
                @elseif ($this->isSubscribed)
                    <flux:badge color="emerald">{{ __('Aktiv') }}</flux:badge>
                    <flux:text>
                        {{ __('Aktueller Tarif: :plan.', ['plan' => $this->plans[$this->activePlan]['name'] ?? '–']) }}
                    </flux:text>
                    <flux:modal.trigger name="cancel-subscription">
                        <flux:button size="sm" variant="ghost" type="button">
                            {{ __('Zum Periodenende kündigen') }}
                        </flux:button>
                    </flux:modal.trigger>
                @else
                    <flux:badge color="zinc">{{ __('Kein Tarif') }}</flux:badge>
                    <flux:text>{{ __('Wählen Sie unten einen Tarif aus.') }}</flux:text>
                @endif
            </div>
        </div>

        {{-- ============ PLAN-AUSWAHL ============ --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">{{ __('Tarife') }}</flux:heading>
                <flux:radio.group wire:model.live="billingCycle" variant="segmented" size="sm">
                    <flux:radio value="monthly" label="{{ __('Monatlich') }}" />
                    <flux:radio value="yearly" label="{{ __('Jährlich (≈17 % Rabatt)') }}" />
                </flux:radio.group>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                @foreach ($this->plans as $key => $plan)
                    @php
                        $priceId = $billingCycle === 'monthly' ? $plan['monthly_price_id'] : $plan['yearly_price_id'];
                        $amount = $billingCycle === 'monthly' ? $plan['monthly_amount'] : $plan['yearly_amount'];
                        $isActive = $this->activePlan === $key;
                        $hasPrice = ! blank($priceId);
                    @endphp
                    <div @class([
                        'rounded-xl border p-5 flex flex-col gap-3',
                        'border-indigo-500 ring-2 ring-indigo-500/20' => $isActive,
                        'border-zinc-200 dark:border-zinc-700' => ! $isActive,
                    ])>
                        <div class="flex items-baseline justify-between">
                            <flux:heading>{{ $plan['name'] }}</flux:heading>
                            @if ($isActive)
                                <flux:badge color="indigo" size="sm">{{ __('Aktuell') }}</flux:badge>
                            @endif
                        </div>

                        <flux:text class="text-2xl font-semibold">
                            @if ($amount === null)
                                {{ __('individuell') }}
                            @else
                                {{ number_format($amount / 100, 0, ',', '.') }} €
                                <flux:text as="span" variant="subtle" class="text-sm font-normal">
                                    {{ $billingCycle === 'monthly' ? __('/Monat') : __('/Jahr') }}
                                </flux:text>
                            @endif
                        </flux:text>

                        @if ($key === 'enterprise')
                            <flux:button :href="'mailto:'.\App\Support\Settings\SystemSetting::get('platform_contact_email')" type="button" variant="outline">
                                {{ __('Demo anfragen') }}
                            </flux:button>
                        @elseif ($isActive && ! $this->onGracePeriod)
                            <flux:button disabled type="button" variant="ghost">
                                {{ __('Aktiver Tarif') }}
                            </flux:button>
                        @elseif (! $hasPrice)
                            <flux:button disabled type="button" variant="ghost">
                                {{ __('Bald verfügbar') }}
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="selectPlan('{{ $key }}', '{{ $billingCycle }}')"
                                type="button"
                                :variant="$isActive ? 'outline' : 'primary'"
                            >
                                @if ($this->isSubscribed && ! $this->onGracePeriod)
                                    {{ __('Auf :plan wechseln', ['plan' => $plan['name']]) }}
                                @else
                                    {{ __(':plan buchen', ['plan' => $plan['name']]) }}
                                @endif
                            </flux:button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ ADD-ONS ============ --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">{{ __('Zusatzleistungen') }}</flux:heading>

            <div class="space-y-3">
                @foreach ($this->addons as $key => $addon)
                    @php
                        $allowQuantity = $addon['allow_quantity'] ?? false;
                        $hasPrice = ! blank($addon['price_id'] ?? null);
                        $isRetainer = ($addon['mode'] ?? 'payment') === 'subscription';
                        $retainerActive = $isRetainer && $this->team()?->subscribed($key);
                    @endphp
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 flex flex-col md:flex-row md:items-center gap-3">
                        <div class="flex-1">
                            <flux:heading size="sm">
                                {{ $addon['name'] }}
                                @if ($isRetainer)
                                    <flux:badge color="indigo" size="sm" inset="top bottom">{{ __('Abo') }}</flux:badge>
                                @endif
                                @if ($retainerActive)
                                    <flux:badge color="emerald" size="sm" inset="top bottom">{{ __('aktiv') }}</flux:badge>
                                @endif
                            </flux:heading>
                            <flux:text variant="subtle" class="text-sm">{{ $addon['description'] }}</flux:text>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($allowQuantity && ! $retainerActive)
                                <flux:input
                                    type="number"
                                    min="1"
                                    max="50"
                                    wire:model="addonQuantities.{{ $key }}"
                                    class="w-20"
                                />
                            @endif

                            @if ($retainerActive)
                                <flux:button
                                    wire:click="cancelAddon('{{ $key }}')"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                >
                                    {{ __('Kündigen') }}
                                </flux:button>
                            @elseif (! $hasPrice)
                                <flux:button disabled type="button" variant="ghost" size="sm">
                                    {{ __('Bald verfügbar') }}
                                </flux:button>
                            @else
                                <flux:button
                                    wire:click="purchaseAddon('{{ $key }}')"
                                    type="button"
                                    variant="primary"
                                    size="sm"
                                >
                                    {{ $isRetainer ? __('Abonnieren') : __('Buchen') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============ RECHNUNGEN ============ --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">{{ __('Rechnungen') }}</flux:heading>

            @php $invoices = collect($this->invoices); @endphp

            @if ($invoices->isEmpty())
                <flux:text variant="subtle">{{ __('Noch keine Rechnungen vorhanden.') }}</flux:text>
            @else
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Datum') }}</flux:table.column>
                            <flux:table.column>{{ __('Betrag') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($invoices as $invoice)
                                <flux:table.row>
                                    <flux:table.cell>{{ $invoice->date()->toFormattedDateString() }}</flux:table.cell>
                                    <flux:table.cell>{{ $invoice->total() }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="$invoice->isPaid() ? 'emerald' : 'amber'">
                                            {{ $invoice->isPaid() ? __('Bezahlt') : __('Offen') }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:link :href="route('billing.invoice', ['invoice' => $invoice->id])">
                                            {{ __('PDF') }}
                                        </flux:link>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </div>

        {{-- ============ KÜNDIGUNGS-MODAL ============ --}}
        <flux:modal name="cancel-subscription" class="max-w-md">
            <div class="space-y-4">
                <flux:heading>{{ __('Tarif kündigen?') }}</flux:heading>
                <flux:text>{{ __('Ihr Abo läuft regulär bis zum Periodenende weiter. Danach wird der Mandant in den Read-Only-Modus versetzt — Ihre Daten bleiben 30 Tage exportierbar.') }}</flux:text>
                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Abbrechen') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="cancelSubscription" type="button" variant="danger">
                        {{ __('Zum Periodenende kündigen') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>

    </x-pages::settings.layout>
</section>
