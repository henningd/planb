<?php

use App\Enums\Industry;
use App\Models\Company;
use App\Models\IndustryTemplate;
use App\Scopes\CurrentCompanyScope;
use App\Support\Backup\BackupCatalog;
use App\Support\Backup\Exporter;
use App\Support\Backup\Importer;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Admin · Branchen-Templates')] class extends Component {
    use WithFileUploads;

    public ?string $editingId = null;

    public string $name = '';

    public string $industry = '';

    public string $description = '';

    public bool $is_active = true;

    public int $sort = 0;

    /**
     * Quelle für den Payload — entweder JSON-Upload oder Snapshot
     * aus einer bestehenden Firma.
     */
    public string $payloadMode = 'keep'; // keep | upload | snapshot

    public $payloadFile = null;

    public ?string $snapshotCompanyId = null;

    public ?string $deletingId = null;

    public ?string $applyingId = null;

    public ?string $applyTargetCompanyId = null;

    public bool $applyConfirming = false;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, IndustryTemplate>
     */
    #[Computed]
    public function templates(): \Illuminate\Database\Eloquent\Collection
    {
        return IndustryTemplate::orderBy('industry')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Company>
     */
    #[Computed]
    public function companies(): \Illuminate\Database\Eloquent\Collection
    {
        return Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->payloadMode = 'upload';
        Flux::modal('template-form')->show();
    }

    public function openEdit(string $id): void
    {
        $template = IndustryTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->industry = $template->industry->value;
        $this->description = (string) $template->description;
        $this->is_active = (bool) $template->is_active;
        $this->sort = $template->sort;
        $this->payloadMode = 'keep';
        Flux::modal('template-form')->show();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'in:'.collect(Industry::cases())->pluck('value')->implode(',')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'sort' => ['integer', 'min:0'],
            'payloadMode' => ['required', 'in:keep,upload,snapshot'],
        ];

        if ($this->payloadMode === 'upload' && $this->payloadFile) {
            $rules['payloadFile'] = ['file', 'mimes:json,txt', 'max:10240'];
        }
        if ($this->payloadMode === 'snapshot') {
            $rules['snapshotCompanyId'] = ['required', 'uuid', 'exists:companies,id'];
        }

        $validated = $this->validate($rules);

        // Payload-Auflösung
        $payload = null;
        if ($this->payloadMode === 'upload' && $this->payloadFile) {
            try {
                $content = file_get_contents($this->payloadFile->getRealPath());
                $payload = json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $this->addError('payloadFile', __('JSON konnte nicht gelesen werden: :msg', ['msg' => $e->getMessage()]));

                return;
            }

            if (! is_array($payload) || ! isset($payload['areas'])) {
                $this->addError('payloadFile', __('Datei ist kein gültiges PlanB-Backup-Payload.'));

                return;
            }
        } elseif ($this->payloadMode === 'snapshot') {
            $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
                ->findOrFail($this->snapshotCompanyId);
            $payload = Exporter::export($company, array_keys(BackupCatalog::all()));
        }

        $data = [
            'name' => $validated['name'],
            'industry' => $validated['industry'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort' => $validated['sort'] ?? 0,
        ];
        if ($payload !== null) {
            $data['payload'] = $payload;
        }

        if ($this->editingId) {
            IndustryTemplate::findOrFail($this->editingId)->update($data);
        } else {
            // Beim Anlegen MUSS ein Payload vorhanden sein.
            if ($payload === null) {
                $this->addError('payloadMode', __('Beim Anlegen muss ein Payload (Upload oder Snapshot) gewählt werden.'));

                return;
            }
            IndustryTemplate::create($data);
        }

        Flux::modal('template-form')->close();
        $this->resetForm();
        unset($this->templates);

        Flux::toast(variant: 'success', text: __('Template gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('template-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            IndustryTemplate::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->templates);
            Flux::modal('template-delete')->close();
            Flux::toast(variant: 'success', text: __('Template gelöscht.'));
        }
    }

    public function openApply(string $id): void
    {
        $this->applyingId = $id;
        $this->applyTargetCompanyId = null;
        $this->applyConfirming = false;
        Flux::modal('template-apply')->show();
    }

    public function confirmApply(): void
    {
        if (! $this->applyTargetCompanyId) {
            Flux::toast(variant: 'warning', text: __('Bitte Ziel-Firma wählen.'));

            return;
        }
        $this->applyConfirming = true;
    }

    public function runApply(): void
    {
        if (! $this->applyingId || ! $this->applyTargetCompanyId) {
            return;
        }

        $template = IndustryTemplate::findOrFail($this->applyingId);
        $target = Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->findOrFail($this->applyTargetCompanyId);

        try {
            Importer::import(
                $target,
                $template->payload ?? ['areas' => []],
                array_keys($template->payload['areas'] ?? []),
                regenerateIds: true,
            );
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Apply fehlgeschlagen: :msg', ['msg' => $e->getMessage()]));

            return;
        }

        $this->applyingId = null;
        $this->applyTargetCompanyId = null;
        $this->applyConfirming = false;
        Flux::modal('template-apply')->close();

        Flux::toast(variant: 'success', text: __('Template auf „:name" angewendet.', ['name' => $target->name]));
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'industry', 'description', 'is_active', 'sort',
            'payloadMode', 'payloadFile', 'snapshotCompanyId',
        ]);
        $this->is_active = true;
        $this->sort = 0;
    }
}; ?>

<section class="w-full">
    <div class="mb-6 rounded-xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
        <strong>{{ __('Superadmin-Modus') }}</strong> – {{ __('Templates werden später beim Onboarding neuer Mandanten zur Auswahl angeboten. Du kannst sie hier auch direkt auf bestehende Firmen anwenden.') }}
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Branchen-Templates') }}</flux:heading>
            <flux:subheading>{{ __('Vorgefertigte Stammdaten-Pakete pro Branche. Onboarding-Wizard wählt daraus, statt jedes Feld manuell pflegen zu müssen.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">
            {{ __('Neues Template') }}
        </flux:button>
    </div>

    @if ($this->templates->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Noch keine Templates angelegt. Erstelle eines per Snapshot aus einer bestehenden Firma oder per JSON-Upload.') }}
            </flux:text>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->templates as $tpl)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <flux:heading size="base">{{ $tpl->name }}</flux:heading>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <flux:badge color="indigo" size="sm">{{ $tpl->industry->label() }}</flux:badge>
                                @if (! $tpl->is_active)
                                    <flux:badge color="zinc" size="sm">{{ __('Inaktiv') }}</flux:badge>
                                @endif
                                <flux:badge color="zinc" size="sm">{{ $tpl->payloadCount() }} {{ __('Datensätze') }}</flux:badge>
                            </div>
                            @if ($tpl->description)
                                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $tpl->description }}</flux:text>
                            @endif
                        </div>
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item icon="play" wire:click="openApply('{{ $tpl->id }}')">
                                    {{ __('Auf Firma anwenden') }}
                                </flux:menu.item>
                                <flux:menu.item icon="pencil" wire:click="openEdit('{{ $tpl->id }}')">
                                    {{ __('Bearbeiten') }}
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $tpl->id }}')">
                                    {{ __('Löschen') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form-Modal --}}
    <flux:modal name="template-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Template bearbeiten') : __('Neues Template') }}</flux:heading>
                <flux:subheading>{{ __('Name + Branche festlegen, Payload aus einer Firma snappen oder JSON hochladen.') }}</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" required placeholder="z. B. Elektriker (Standard)" />
                <flux:select wire:model="industry" :label="__('Branche')" required>
                    <flux:select.option value="">{{ __('— bitte wählen —') }}</flux:select.option>
                    @foreach (Industry::options() as $opt)
                        <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="2" placeholder="{{ __('Was bringt dieses Template mit? An welche Größenordnung richtet es sich?') }}" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />
                <flux:switch wire:model="is_active" :label="__('Aktiv (Onboarding zeigt nur aktive)')" />
            </div>

            <div class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div>
                    <flux:heading size="base">{{ __('Payload-Quelle') }}</flux:heading>
                    <flux:subheading>
                        @if ($editingId)
                            {{ __('Vorhandenen Payload behalten, neues JSON hochladen oder aus einer Firma neu erstellen.') }}
                        @else
                            {{ __('JSON hochladen oder Snapshot aus einer bestehenden Firma erzeugen.') }}
                        @endif
                    </flux:subheading>
                </div>

                <div class="flex flex-wrap gap-3 text-sm">
                    @if ($editingId)
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" wire:model.live="payloadMode" value="keep">
                            {{ __('Behalten') }}
                        </label>
                    @endif
                    <label class="inline-flex items-center gap-1">
                        <input type="radio" wire:model.live="payloadMode" value="upload">
                        {{ __('JSON-Upload') }}
                    </label>
                    <label class="inline-flex items-center gap-1">
                        <input type="radio" wire:model.live="payloadMode" value="snapshot">
                        {{ __('Snapshot aus Firma') }}
                    </label>
                </div>

                @if ($payloadMode === 'upload')
                    <flux:input type="file" accept="application/json,.json" wire:model="payloadFile" :label="__('Payload-Datei')" />
                @elseif ($payloadMode === 'snapshot')
                    <flux:select wire:model="snapshotCompanyId" :label="__('Quelle')">
                        <flux:select.option value="">{{ __('— Firma wählen —') }}</flux:select.option>
                        @foreach ($this->companies as $c)
                            <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Es werden alle Bereiche der Firma exportiert und im Template gespeichert.') }}
                    </flux:text>
                @endif
            </div>

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

    {{-- Apply-Modal --}}
    <flux:modal name="template-apply" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Template anwenden') }}</flux:heading>
                <flux:subheading>{{ __('Wählt die Ziel-Firma. Bestehende Daten in den Template-Bereichen werden ersetzt.') }}</flux:subheading>
            </div>

            <flux:select wire:model="applyTargetCompanyId" :label="__('Ziel-Firma')">
                <flux:select.option value="">{{ __('— Firma wählen —') }}</flux:select.option>
                @foreach ($this->companies as $c)
                    <flux:select.option value="{{ $c->id }}">{{ $c->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($applyConfirming)
                <div class="rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
                    <strong>{{ __('Wirklich anwenden?') }}</strong>
                    {{ __('Aktuelle Daten der Ziel-Firma in den im Template enthaltenen Bereichen werden ersetzt.') }}
                </div>
            @endif

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($applyConfirming)
                    <flux:button variant="filled" type="button" wire:click="$set('applyConfirming', false)">{{ __('Abbrechen') }}</flux:button>
                    <flux:button variant="danger" type="button" icon="play" wire:click="runApply">{{ __('Jetzt anwenden') }}</flux:button>
                @else
                    <flux:modal.close>
                        <flux:button variant="filled" type="button">{{ __('Schließen') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="button" wire:click="confirmApply">{{ __('Vorbereiten') }}</flux:button>
                @endif
            </div>
        </div>
    </flux:modal>

    {{-- Delete-Modal --}}
    <flux:modal name="template-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Template löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Das Template wird unwiderruflich gelöscht. Bereits angewendete Daten bleiben unangetastet.') }}</flux:subheading>
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
