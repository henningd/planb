<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Employee;
use App\Models\Scenario;
use App\Services\Sms\SmsGatewayContract;
use App\Services\Sms\SmsResult;
use App\Support\TemplatePlaceholders;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kommunikations-Vorlagen')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $audience = '';

    public string $channel = '';

    public ?string $scenario_id = null;

    public string $subject = '';

    public string $body = '';

    public string $fallback = '';

    public ?string $deletingId = null;

    public ?string $previewId = null;

    public ?string $smsTemplateId = null;

    /**
     * @var list<string>
     */
    public array $smsRecipients = [];

    /**
     * @var list<array{to: string, name: string, success: bool, error: ?string}>
     */
    public array $smsResults = [];

    public bool $smsConfirming = false;

    public function mount(): void
    {
        $this->audience = CommunicationAudience::Employees->value;
        $this->channel = CommunicationChannel::Email->value;
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, Scenario>
     */
    #[Computed]
    public function scenarios(): Collection
    {
        return Scenario::orderBy('name')->get();
    }

    /**
     * Templates grouped by audience, ordered by `sort` then `name`.
     *
     * @return array<string, Collection<int, CommunicationTemplate>>
     */
    #[Computed]
    public function templatesByAudience(): array
    {
        $templates = CommunicationTemplate::with('scenario')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        $grouped = [];
        foreach (CommunicationAudience::cases() as $case) {
            $grouped[$case->value] = $templates->where('audience', $case);
        }

        return $grouped;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function placeholders(): array
    {
        return TemplatePlaceholders::known();
    }

    /**
     * @return array{template: CommunicationTemplate, subject: ?string, body: string, fallback: ?string}|null
     */
    #[Computed]
    public function preview(): ?array
    {
        if ($this->previewId === null) {
            return null;
        }

        $template = CommunicationTemplate::with('scenario')->find($this->previewId);

        if (! $template) {
            return null;
        }

        $company = Auth::user()->currentCompany();

        return [
            'template' => $template,
            'subject' => $template->subject
                ? TemplatePlaceholders::resolve($template->subject, $company)
                : null,
            'body' => TemplatePlaceholders::resolve($template->body, $company),
            'fallback' => $template->fallback
                ? TemplatePlaceholders::resolve($template->fallback, $company)
                : null,
        ];
    }

    public function openCreate(?string $audience = null): void
    {
        $this->resetForm();
        if ($audience) {
            $this->audience = $audience;
        }
        Flux::modal('template-form')->show();
    }

    public function openEdit(string $id): void
    {
        $template = CommunicationTemplate::findOrFail($id);

        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->audience = $template->audience->value;
        $this->channel = $template->channel->value;
        $this->scenario_id = $template->scenario_id;
        $this->subject = (string) $template->subject;
        $this->body = $template->body;
        $this->fallback = (string) $template->fallback;

        Flux::modal('template-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'in:'.collect(CommunicationAudience::cases())->pluck('value')->implode(',')],
            'channel' => ['required', 'in:'.collect(CommunicationChannel::cases())->pluck('value')->implode(',')],
            'scenario_id' => ['nullable', 'uuid', 'exists:scenarios,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:4000'],
            'fallback' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->editingId) {
            CommunicationTemplate::findOrFail($this->editingId)->update($validated);
        } else {
            CommunicationTemplate::create($validated);
        }

        Flux::modal('template-form')->close();
        $this->resetForm();
        unset($this->templatesByAudience);

        Flux::toast(variant: 'success', text: __('Vorlage gespeichert.'));
    }

    public function openPreview(string $id): void
    {
        $this->previewId = $id;
        unset($this->preview);
        Flux::modal('template-preview')->show();
    }

    /**
     * Öffnet das SMS-Versand-Modal und prefilled alle Mitarbeiter mit
     * gepflegter Mobilnummer als Empfänger.
     */
    public function openSmsSend(string $id): void
    {
        $this->smsTemplateId = $id;
        $this->smsResults = [];
        $this->smsConfirming = false;
        $this->smsRecipients = Employee::query()
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->pluck('id')
            ->all();

        Flux::modal('template-sms-send')->show();
    }

    /**
     * Erste Stufe: bei Klick auf „Jetzt senden" wird die Confirm-Stufe
     * aktiviert. Erst der zweite Klick (`sendSms`) löst den Versand aus.
     */
    public function confirmSendSms(): void
    {
        if (empty($this->smsRecipients)) {
            Flux::toast(variant: 'warning', text: __('Keine Empfänger ausgewählt.'));

            return;
        }

        $this->smsConfirming = true;
    }

    public function cancelSendSms(): void
    {
        $this->smsConfirming = false;
    }

    public function sendSms(SmsGatewayContract $gateway): void
    {
        if (! $this->smsTemplateId) {
            return;
        }

        $template = CommunicationTemplate::findOrFail($this->smsTemplateId);
        if ($template->channel !== CommunicationChannel::Sms) {
            Flux::toast(variant: 'warning', text: __('Diese Vorlage ist kein SMS-Kanal.'));

            return;
        }

        $body = TemplatePlaceholders::resolve($template->body, Auth::user()->currentCompany());
        $recipients = Employee::query()
            ->whereIn('id', $this->smsRecipients)
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->get();

        if ($recipients->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('Keine gültigen Empfänger ausgewählt.'));

            return;
        }

        $results = [];
        foreach ($recipients as $employee) {
            $result = $gateway->send($employee->mobile_phone, $body);
            $results[] = [
                'to' => $result->to,
                'name' => $employee->fullName(),
                'success' => $result->success,
                'error' => $result->errorMessage,
            ];
        }

        $this->smsResults = $results;
        $this->smsConfirming = false;

        $ok = collect($results)->where('success', true)->count();
        $err = count($results) - $ok;

        DB::table('audit_log_entries')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'company_id' => $template->company_id,
            'user_id' => Auth::id(),
            'entity_type' => 'CommunicationTemplate',
            'entity_id' => $template->id,
            'entity_label' => $template->name,
            'action' => 'sms.sent',
            'changes' => json_encode([
                'sent' => $ok,
                'failed' => $err,
                'recipients' => collect($results)->pluck('to')->all(),
            ]),
            'created_at' => now(),
        ]);

        Flux::toast(
            variant: $err === 0 ? 'success' : 'warning',
            text: __(':ok von :total SMS verschickt.', ['ok' => $ok, 'total' => count($results)]),
        );
    }

    /**
     * Liste aller Mitarbeiter mit Mobilnummer für den Empfänger-Picker.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function smsCandidates(): Collection
    {
        return Employee::query()
            ->whereNotNull('mobile_phone')
            ->where('mobile_phone', '!=', '')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function smsBodyPreview(): string
    {
        if (! $this->smsTemplateId) {
            return '';
        }

        $template = CommunicationTemplate::find($this->smsTemplateId);
        if (! $template) {
            return '';
        }

        return TemplatePlaceholders::resolve($template->body, Auth::user()->currentCompany());
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('template-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            CommunicationTemplate::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->templatesByAudience);
            Flux::modal('template-delete')->close();
            Flux::toast(variant: 'success', text: __('Vorlage gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'scenario_id', 'subject', 'body', 'fallback']);
        $this->audience = CommunicationAudience::Employees->value;
        $this->channel = CommunicationChannel::Email->value;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Kommunikations-Vorlagen') }}</flux:heading>
            <flux:subheading>
                {{ __('Vorformulierte Texte für Mitarbeiter, Kunden, Presse und Behörden. Im Ernstfall Zeit und Nerven sparen.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Vorlage') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="space-y-6">
        @foreach (\App\Enums\CommunicationAudience::cases() as $audience)
            @php($templates = $this->templatesByAudience[$audience->value])
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:heading size="base">{{ $audience->label() }}</flux:heading>
                        <flux:badge color="zinc" size="sm">{{ $templates->count() }}</flux:badge>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openCreate('{{ $audience->value }}')" :disabled="! $this->hasCompany">
                        {{ __('Hinzufügen') }}
                    </flux:button>
                </div>

                @forelse ($templates as $template)
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $template->name }}</span>
                                <flux:badge color="sky" size="sm" icon="{{ $template->channel->icon() }}">{{ $template->channel->label() }}</flux:badge>
                                @if ($template->scenario)
                                    <flux:badge color="zinc" size="sm" icon="bolt">{{ $template->scenario->name }}</flux:badge>
                                @endif
                            </div>
                            @if ($template->subject)
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Betreff') }}:</span> {{ $template->subject }}
                                </flux:text>
                            @endif
                            <flux:text class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $template->body }}
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($template->channel === \App\Enums\CommunicationChannel::Sms)
                                <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="openSmsSend('{{ $template->id }}')">
                                    {{ __('SMS senden') }}
                                </flux:button>
                            @endif
                            <flux:button size="sm" variant="filled" icon="eye" wire:click="openPreview('{{ $template->id }}')">
                                {{ __('Vorschau') }}
                            </flux:button>
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="openEdit('{{ $template->id }}')">
                                        {{ __('Bearbeiten') }}
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $template->id }}')">
                                        {{ __('Löschen') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch keine Vorlage für diese Zielgruppe.') }}
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>

    <flux:modal name="template-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Vorlage bearbeiten') : __('Neue Vorlage') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Sie können Platzhalter verwenden, die beim Anzeigen automatisch ersetzt werden.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Mitarbeiter-Erstmeldung SMS" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="audience" :label="__('Zielgruppe')" required>
                    @foreach (\App\Enums\CommunicationAudience::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="channel" :label="__('Kanal')" required>
                    @foreach (\App\Enums\CommunicationChannel::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:select wire:model="scenario_id" :label="__('Szenario (optional)')">
                <flux:select.option value="">{{ __('Keinem Szenario zugeordnet') }}</flux:select.option>
                @foreach ($this->scenarios as $scenario)
                    <flux:select.option value="{{ $scenario->id }}">{{ $scenario->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="subject" :label="__('Betreff (optional)')" type="text" placeholder="z. B. Wichtige Information zum Betriebsablauf" />

            <flux:field>
                <flux:label>{{ __('Text') }}</flux:label>
                <flux:description>
                    {{ __('Verfügbare Platzhalter:') }}
                    @foreach ($this->placeholders as $key => $label)
                        <code class="mx-0.5 rounded bg-zinc-100 px-1 py-0.5 text-[11px] dark:bg-zinc-800">&#123;&#123; {{ $key }} &#125;&#125;</code>
                    @endforeach
                </flux:description>
                <flux:textarea wire:model="body" rows="8" required :placeholder="__('Sehr geehrte Damen und Herren, bei').' &#123;&#123; firma &#125;&#125; '.__('ist am').' &#123;&#123; zeitpunkt &#125;&#125; '.__('ein Vorfall eingetreten. …')" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Alternativkanal / Fallback (optional)') }}</flux:label>
                <flux:description>{{ __('Was tun, wenn der primäre Kanal ausgefallen ist? Z. B. „SMS an alle Mitarbeiter", „Aushang am Eingang".') }}</flux:description>
                <flux:textarea wire:model="fallback" rows="3" />
            </flux:field>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="template-preview" class="max-w-2xl">
        @php($preview = $this->preview)
        @if ($preview)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ $preview['template']->name }}</flux:heading>
                    <flux:subheading>
                        {{ $preview['template']->audience->label() }} · {{ $preview['template']->channel->label() }}
                    </flux:subheading>
                </div>

                <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($preview['subject'])
                        <div>
                            <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Betreff') }}</div>
                            <div class="mt-0.5 font-medium">{{ $preview['subject'] }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Text') }}</div>
                        <div class="mt-0.5 whitespace-pre-wrap text-zinc-800 dark:text-zinc-100">{{ $preview['body'] }}</div>
                    </div>
                    @if ($preview['fallback'])
                        <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Wenn Kanal ausgefallen') }}</div>
                            <div class="mt-0.5 whitespace-pre-wrap text-zinc-700 dark:text-zinc-200">{{ $preview['fallback'] }}</div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Schließen') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal name="template-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Vorlage löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="template-sms-send" class="max-w-2xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('SMS senden') }}</flux:heading>
                <flux:subheading>
                    {{ __('Versand über seven.io. Mitarbeiter ohne Mobilnummer werden hier nicht angeboten.') }}
                </flux:subheading>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-800/50">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Nachricht') }}</flux:text>
                <div class="mt-1 whitespace-pre-line">{{ $this->smsBodyPreview() }}</div>
            </div>

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
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $candidate->mobile_phone }}</div>
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
                            @if ($r['success'])
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
</section>
