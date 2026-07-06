{{--
    Gemeinsame Versand-Modale (SMS & E-Mail) für Kommunikationsvorlagen.
    Erwartet, dass die einbindende Livewire-Komponente das Trait
    \App\Concerns\SendsCommunicationTemplates nutzt (Properties + Methoden).
--}}
<flux:modal name="template-sms-send" class="max-w-2xl">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">{{ __('SMS senden') }}</flux:heading>
            <flux:subheading>
                {{ __('Versand über seven.io. Mitarbeiter ohne Mobilnummer werden hier nicht angeboten.') }}
            </flux:subheading>
        </div>

        @unless (app(\App\Services\Sms\SmsGatewayContract::class)->isConfigured())
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('SMS-Gateway nicht konfiguriert') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Es werden KEINE echten SMS verschickt — der Versand wird nur simuliert. Hinterlege SEVENIO_API_KEY in der Server-Konfiguration, um den Versand zu aktivieren.') }}
                </flux:callout.text>
            </flux:callout>
        @endunless

        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Nachricht') }}</flux:text>
            <div class="mt-1 whitespace-pre-line">{{ $this->smsBodyPreview() }}</div>
        </div>

        @if ($this->smsCandidates->isNotEmpty())
            <div class="flex items-center justify-between">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __(':selected von :total ausgewählt', ['selected' => count($smsRecipients), 'total' => $this->smsCandidates->count()]) }}
                </flux:text>
                <div class="flex gap-2">
                    <flux:button size="xs" variant="subtle" wire:click="selectAllSmsRecipients">{{ __('Alle auswählen') }}</flux:button>
                    <flux:button size="xs" variant="subtle" wire:click="deselectAllSmsRecipients">{{ __('Alle abwählen') }}</flux:button>
                </div>
            </div>
        @endif

        <div class="max-h-72 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            @forelse ($this->smsCandidates as $candidate)
                <label class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-2 last:border-b-0 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            wire:model="smsRecipients"
                            value="{{ $candidate->id }}"
                            class="rounded border-zinc-300 dark:border-zinc-600"
                        >
                        <div>
                            <div class="text-sm font-medium">{{ $candidate->fullName() }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ \App\Support\PhoneFormat::display($candidate->mobile_phone) }}</div>
                        </div>
                    </div>
                    @if ($candidate->is_key_personnel)
                        <flux:badge color="amber" size="sm">{{ __('Schlüssel') }}</flux:badge>
                    @endif
                </label>
            @empty
                <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Keine Mitarbeiter mit gepflegter Mobilnummer.') }}
                </div>
            @endforelse
        </div>

        @if (! empty($smsResults))
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-100 px-4 py-2 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    {{ __('Versand-Ergebnis') }}
                </div>
                @foreach ($smsResults as $r)
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-4 py-2 text-sm last:border-b-0 dark:border-zinc-800">
                        <div>
                            <div class="font-medium">{{ $r['name'] }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $r['to'] }}</div>
                        </div>
                        @if ($r['success'] && ! app(\App\Services\Sms\SmsGatewayContract::class)->isConfigured())
                            <flux:badge color="amber" size="sm" icon="exclamation-triangle">{{ __('Simuliert — kein Gateway') }}</flux:badge>
                        @elseif ($r['success'])
                            <flux:badge color="emerald" size="sm" icon="check">{{ __('OK') }}</flux:badge>
                        @else
                            <flux:badge color="rose" size="sm" icon="x-mark">{{ $r['error'] ?? __('Fehler') }}</flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($smsConfirming)
            <div class="rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
                <strong>{{ __('Wirklich senden?') }}</strong>
                {{ __('Du verschickst gleich :n SMS. Das kann nicht zurückgenommen werden und kostet pro Empfänger.', ['n' => count($smsRecipients)]) }}
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            @if ($smsConfirming)
                <flux:button variant="filled" type="button" wire:click="cancelSendSms">{{ __('Abbrechen') }}</flux:button>
                <flux:button variant="danger" type="button" icon="paper-airplane" wire:click="sendSms">
                    {{ __(':n SMS jetzt verschicken', ['n' => count($smsRecipients)]) }}
                </flux:button>
            @else
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Schließen') }}</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="primary"
                    type="button"
                    icon="paper-airplane"
                    wire:click="confirmSendSms"
                    :disabled="empty($smsRecipients)"
                >
                    {{ __('Senden vorbereiten') }} ({{ count($smsRecipients) }})
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>

<flux:modal name="template-email-send" class="max-w-2xl">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">{{ __('E-Mail senden') }}</flux:heading>
            <flux:subheading>
                {{ __('Wählen Sie die Empfänger. Versand läuft direkt — Erfolg/Fehler werden pro Empfänger protokolliert.') }}
            </flux:subheading>
        </div>

        @if ($emailTemplateId && empty($emailResults))
            <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <div class="font-medium">{{ __('Betreff') }}</div>
                <div class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $this->emailSubjectPreview() }}</div>
                <div class="mt-3 font-medium">{{ __('Inhalt') }}</div>
                <div class="mt-1 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $this->emailBodyPreview() }}</div>
            </div>

            <div>
                <flux:label>{{ __('Empfänger') }} ({{ $this->emailCandidates->count() }} {{ __('mit E-Mail-Adresse') }})</flux:label>
                <div class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    @foreach ($this->emailCandidates as $employee)
                        <flux:checkbox
                            wire:model="emailRecipients"
                            value="{{ $employee->id }}"
                            :label="$employee->fullName().' · '.$employee->email"
                        />
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($emailResults))
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-100 px-4 py-2 text-sm font-medium dark:border-zinc-800">
                    {{ __('Versand-Ergebnisse') }}
                </div>
                <div class="max-h-72 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
                    @foreach ($emailResults as $result)
                        <div class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                            <div>
                                <div class="font-medium">{{ $result['name'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $result['to'] }}</div>
                                @if (! $result['success'] && $result['error'])
                                    <div class="text-xs text-rose-600 dark:text-rose-400">{{ $result['error'] }}</div>
                                @endif
                            </div>
                            <flux:badge :color="$result['success'] ? 'emerald' : 'rose'" size="sm">
                                {{ $result['success'] ? __('verschickt') : __('fehlgeschlagen') }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            @if (! empty($emailResults))
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Schließen') }}</flux:button>
                </flux:modal.close>
            @elseif ($emailConfirming)
                <flux:button type="button" variant="filled" wire:click="cancelSendEmail">
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button type="button" variant="primary" icon="paper-airplane" wire:click="sendEmail">
                    {{ __('Jetzt verschicken') }}
                </flux:button>
            @else
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" icon="check" wire:click="confirmSendEmail">
                    {{ __('Senden vorbereiten') }} ({{ count($emailRecipients) }})
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
