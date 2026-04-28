<?php

use App\Models\AccountDeletionRequest;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Daten & Datenschutz')] class extends Component {
    public string $reason = '';

    /**
     * Submit the account deletion request.
     */
    public function requestDeletion(): void
    {
        $user = Auth::user();

        if ($this->pendingRequest()) {
            Flux::toast(variant: 'warning', text: __('Es liegt bereits eine offene Löschanfrage vor.'));

            return;
        }

        $validated = $this->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'reason' => $validated['reason'] ?? null,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $this->reset('reason');

        unset($this->pendingRequest);

        Flux::modal('confirm-account-deletion')->close();

        Flux::toast(
            variant: 'success',
            heading: __('Antrag erfasst'),
            text: __('Bearbeitung innerhalb von 30 Tagen gemäß Art. 17 DSGVO.'),
        );
    }

    #[Computed(persist: false)]
    public function pendingRequest(): ?AccountDeletionRequest
    {
        return AccountDeletionRequest::query()
            ->where('user_id', Auth::id())
            ->where('status', AccountDeletionRequest::STATUS_PENDING)
            ->latest('requested_at')
            ->first();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Daten & Datenschutz') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Daten & Datenschutz')"
        :subheading="__('Eigene Daten exportieren oder Löschung beantragen')"
    >
        <section class="space-y-4">
            <flux:heading>{{ __('Daten exportieren') }}</flux:heading>
            <flux:subheading>
                {{ __('Lade ein JSON-Dokument mit Deinen Account-Daten, Mandanten-Mitgliedschaften und Deinen eigenen Audit-Log-Einträgen herunter (Art. 15 DSGVO).') }}
            </flux:subheading>

            <flux:button
                variant="primary"
                icon="arrow-down-tray"
                :href="route('settings.data-privacy.export')"
                data-test="data-privacy-export-button"
            >
                {{ __('JSON herunterladen') }}
            </flux:button>
        </section>

        <flux:separator class="my-10" />

        <section class="space-y-4">
            <flux:heading>{{ __('Account-Löschung beantragen') }}</flux:heading>
            <flux:subheading>
                {{ __('Stelle eine Löschanfrage gemäß Art. 17 DSGVO. Ein Administrator prüft die Anfrage und bearbeitet sie innerhalb von 30 Tagen manuell.') }}
            </flux:subheading>

            @if ($this->pendingRequest)
                <flux:callout icon="clock" variant="warning">
                    <flux:callout.heading>{{ __('Löschanfrage in Bearbeitung') }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ __('Anfrage vom :date ist in Bearbeitung. Du erhältst eine Rückmeldung, sobald sie abgeschlossen ist.', [
                            'date' => $this->pendingRequest->requested_at->format('d.m.Y'),
                        ]) }}
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:modal.trigger name="confirm-account-deletion">
                    <flux:button variant="danger" data-test="data-privacy-delete-button">
                        {{ __('Löschung beantragen') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:modal name="confirm-account-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
                    <form wire:submit="requestDeletion" class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Löschung Deines Accounts beantragen?') }}</flux:heading>
                            <flux:subheading>
                                {{ __('Wir prüfen Deine Anfrage und löschen Deine personenbezogenen Daten innerhalb von 30 Tagen, sofern keine gesetzlichen Aufbewahrungspflichten entgegenstehen.') }}
                            </flux:subheading>
                        </div>

                        <flux:textarea
                            wire:model="reason"
                            :label="__('Begründung (optional)')"
                            rows="4"
                            maxlength="2000"
                        />

                        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                            <flux:modal.close>
                                <flux:button type="button" variant="filled">
                                    {{ __('Abbrechen') }}
                                </flux:button>
                            </flux:modal.close>

                            <flux:button
                                variant="danger"
                                type="submit"
                                data-test="data-privacy-confirm-delete-button"
                            >
                                {{ __('Anfrage absenden') }}
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endif
        </section>
    </x-pages::settings.layout>
</section>
