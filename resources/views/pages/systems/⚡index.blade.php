<?php

use App\Enums\SystemCategory;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\SystemPriority;
use App\Support\Duration;
use App\Support\IndustryTemplates;
use App\Support\SystemImport;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Systeme')] class extends Component {
    use WithFileUploads;
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public ?int $system_priority_id = null;

    public ?int $rto_minutes = null;

    public ?int $rpo_minutes = null;

    /** @var array<int> */
    public array $service_provider_ids = [];

    public ?int $deletingId = null;

    public string $templateKey = '';

    public $importFile = null;

    public string $importJson = '';

    public function mount(): void
    {
        $this->category = SystemCategory::Basisbetrieb->value;
        $this->templateKey = IndustryTemplates::defaultFor(Auth::user()->currentCompany()?->industry) ?? '';
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, SystemPriority>
     */
    #[Computed]
    public function priorities(): Collection
    {
        return SystemPriority::orderBy('sort')->get();
    }

    /**
     * @return Collection<int, ServiceProvider>
     */
    #[Computed]
    public function providers(): Collection
    {
        return ServiceProvider::orderBy('name')->get();
    }

    /**
     * Systems grouped by their category. Keyed by the enum value.
     *
     * @return array<string, Collection<int, System>>
     */
    #[Computed]
    public function systemsByCategory(): array
    {
        $systems = System::with(['priority', 'serviceProviders'])->orderBy('name')->get();
        $grouped = [];

        foreach (SystemCategory::cases() as $category) {
            $grouped[$category->value] = $systems->where('category', $category);
        }

        return $grouped;
    }

    public function openCreate(?string $category = null): void
    {
        $this->resetForm();
        if ($category) {
            $this->category = $category;
        }
        Flux::modal('system-form')->show();
    }

    public function openEdit(int $id): void
    {
        $system = System::with('serviceProviders')->findOrFail($id);

        $this->editingId = $system->id;
        $this->name = $system->name;
        $this->description = (string) $system->description;
        $this->category = $system->category->value;
        $this->system_priority_id = $system->system_priority_id;
        $this->rto_minutes = $system->rto_minutes;
        $this->rpo_minutes = $system->rpo_minutes;
        $this->service_provider_ids = $system->serviceProviders->pluck('id')->all();

        Flux::modal('system-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validDurations = array_keys(Duration::OPTIONS);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'in:'.collect(SystemCategory::cases())->pluck('value')->implode(',')],
            'system_priority_id' => ['nullable', 'integer', 'exists:system_priorities,id'],
            'rto_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'rpo_minutes' => ['nullable', 'integer', 'in:'.implode(',', $validDurations)],
            'service_provider_ids' => ['array'],
            'service_provider_ids.*' => ['integer', 'exists:service_providers,id'],
        ]);

        $providerIds = $validated['service_provider_ids'] ?? [];
        unset($validated['service_provider_ids']);

        $system = $this->editingId
            ? tap(System::findOrFail($this->editingId))->update($validated)
            : System::create($validated);

        $system->serviceProviders()->sync($providerIds);

        Flux::modal('system-form')->close();
        $this->resetForm();
        unset($this->systemsByCategory);

        Flux::toast(variant: 'success', text: __('System gespeichert.'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('system-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            System::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->systemsByCategory);
            Flux::modal('system-delete')->close();
            Flux::toast(variant: 'success', text: __('System gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'system_priority_id', 'rto_minutes', 'rpo_minutes', 'service_provider_ids']);
        $this->category = SystemCategory::Basisbetrieb->value;
    }

    /**
     * @return array<string, array{label: string, hint: string, count: int}>
     */
    public function templateCatalog(): array
    {
        return IndustryTemplates::catalog();
    }

    public function openTemplate(): void
    {
        Flux::modal('system-template')->show();
    }

    public function openImport(): void
    {
        $this->reset(['importFile', 'importJson']);
        Flux::modal('system-import')->show();
    }

    public function import(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $raw = null;

        if ($this->importFile) {
            $validated = $this->validate(
                ['importFile' => ['file', 'mimes:json,txt', 'max:512']],
                ['importFile.max' => __('Datei zu groß (max. 512 KB).')],
            );
            $raw = @file_get_contents($this->importFile->getRealPath());
        } elseif (trim($this->importJson) !== '') {
            $raw = $this->importJson;
        }

        if (! $raw) {
            $this->addError('importJson', __('Bitte JSON-Datei hochladen oder Inhalt einfügen.'));

            return;
        }

        $result = SystemImport::fromJson($raw);

        if ($result->hasErrors()) {
            $this->addError('importJson', $result->firstError());

            return;
        }

        $company = Auth::user()->currentCompany();
        $priorityIdByName = $company->systemPriorities()->pluck('id', 'name');
        $existingNames = System::pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($result, $priorityIdByName, $existingNames, &$imported, &$skipped) {
            foreach ($result->systems as $entry) {
                if (in_array(mb_strtolower(trim($entry['name'])), $existingNames, true)) {
                    $skipped++;

                    continue;
                }

                System::create([
                    'name' => $entry['name'],
                    'description' => $entry['description'] ?? null,
                    'category' => $entry['category'],
                    'system_priority_id' => isset($entry['priority']) && $entry['priority']
                        ? ($priorityIdByName[$entry['priority']] ?? null)
                        : null,
                    'rto_minutes' => $entry['rto_minutes'] ?? null,
                    'rpo_minutes' => $entry['rpo_minutes'] ?? null,
                ]);

                $imported++;
            }
        });

        unset($this->systemsByCategory);
        Flux::modal('system-import')->close();
        $this->reset(['importFile', 'importJson']);

        $message = __(':count Systeme importiert.', ['count' => $imported]);
        if ($skipped > 0) {
            $message .= ' '.__(':count bereits vorhandene übersprungen.', ['count' => $skipped]);
        }

        Flux::toast(variant: 'success', text: $message);
    }

    public function loadTemplate(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        abort_unless(IndustryTemplates::has($this->templateKey), 422);

        $company = Auth::user()->currentCompany();
        $systems = IndustryTemplates::systemsFor($this->templateKey) ?? [];
        $priorityIdByName = $company->systemPriorities()->pluck('id', 'name');
        $existingNames = System::pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($systems, $priorityIdByName, $existingNames, &$imported, &$skipped) {
            foreach ($systems as $entry) {
                if (in_array(mb_strtolower(trim($entry['name'])), $existingNames, true)) {
                    $skipped++;

                    continue;
                }

                System::create([
                    'name' => $entry['name'],
                    'description' => $entry['description'],
                    'category' => $entry['category'],
                    'system_priority_id' => $entry['priority'] ? ($priorityIdByName[$entry['priority']] ?? null) : null,
                    'rto_minutes' => $entry['rto_minutes'],
                    'rpo_minutes' => $entry['rpo_minutes'],
                ]);

                $imported++;
            }
        });

        unset($this->systemsByCategory);
        Flux::modal('system-template')->close();

        $message = __(':count Systeme aus Vorlage geladen.', ['count' => $imported]);
        if ($skipped > 0) {
            $message .= ' '.__(':count bereits vorhandene übersprungen.', ['count' => $skipped]);
        }

        Flux::toast(variant: 'success', text: $message);
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Systeme & Betriebskontinuität') }}</flux:heading>
            <flux:subheading>
                {{ __('Welche Systeme braucht Ihr Betrieb – und in welcher Reihenfolge müssen sie im Ernstfall zurück ans Netz?') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="filled" icon="sparkles" wire:click="openTemplate" :disabled="! $this->hasCompany">
                {{ __('Vorlage laden') }}
            </flux:button>
            <flux:button variant="filled" icon="arrow-up-tray" wire:click="openImport" :disabled="! $this->hasCompany">
                {{ __('Importieren') }}
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
                {{ __('Neues System') }}
            </flux:button>
        </div>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an, bevor Sie Systeme hinzufügen.') }}
        </div>
    @endunless

    <div class="space-y-6">
        @foreach (\App\Enums\SystemCategory::cases() as $category)
            @php($systems = $this->systemsByCategory[$category->value])
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <flux:heading size="base">{{ $category->label() }}</flux:heading>
                            <flux:badge color="zinc" size="sm">{{ $systems->count() }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $category->description() }}
                        </flux:text>
                    </div>
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openCreate('{{ $category->value }}')" :disabled="! $this->hasCompany">
                        {{ __('Hinzufügen') }}
                    </flux:button>
                </div>

                @forelse ($systems as $system)
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 last:border-b-0 dark:border-zinc-800">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $system->name }}</span>
                                @if ($system->priority)
                                    <flux:badge
                                        :color="match ($system->priority->sort) { 1 => 'rose', 2 => 'amber', default => 'zinc' }"
                                        size="sm"
                                    >
                                        {{ $system->priority->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Ohne Priorität') }}</flux:badge>
                                @endif
                            </div>
                            @if ($system->description)
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $system->description }}
                                </flux:text>
                            @endif
                            @if ($system->rto_minutes || $system->rpo_minutes)
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                                    @if ($system->rto_minutes)
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.clock class="h-3.5 w-3.5 text-zinc-400" />
                                            <span>{{ __('Max. Ausfall') }}: <span class="font-medium">{{ \App\Support\Duration::format($system->rto_minutes) }}</span></span>
                                        </div>
                                    @endif
                                    @if ($system->rpo_minutes)
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.archive-box class="h-3.5 w-3.5 text-zinc-400" />
                                            <span>{{ __('Max. Datenverlust') }}: <span class="font-medium">{{ \App\Support\Duration::format($system->rpo_minutes) }}</span></span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @if ($system->serviceProviders->isNotEmpty())
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <flux:icon.wrench-screwdriver class="h-3.5 w-3.5 text-zinc-400" />
                                    @foreach ($system->serviceProviders as $p)
                                        <flux:badge color="zinc" size="sm">{{ $p->name }}@if ($p->hotline) · {{ $p->hotline }}@endif</flux:badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEdit({{ $system->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $system->id }})" />
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch kein System in dieser Kategorie.') }}
                        </flux:text>
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>

    <flux:modal name="system-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('System bearbeiten') : __('Neues System anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Was ist das System, wofür wird es gebraucht und wie wichtig ist es?') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Warenwirtschaft, Telefonanlage" />

            <flux:textarea
                wire:model="description"
                :label="__('Beschreibung')"
                rows="3"
                placeholder="Wofür wird dieses System genutzt?"
            />

            <flux:select wire:model="category" :label="__('Kategorie')" required>
                @foreach (\App\Enums\SystemCategory::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="system_priority_id" :label="__('Priorität')" placeholder="Keine">
                <flux:select.option value="">{{ __('Ohne Priorität') }}</flux:select.option>
                @foreach ($this->priorities as $priority)
                    <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Max. Ausfallzeit') }}</flux:label>
                    <flux:description>
                        {{ __('Wie lange darf das System maximal ausfallen, bevor der Betrieb ernsthaft leidet?') }}
                        <span class="text-zinc-400">· {{ __('Fachbegriff: RTO') }}</span>
                    </flux:description>
                    <flux:select wire:model="rto_minutes">
                        <flux:select.option value="">{{ __('Nicht definiert') }}</flux:select.option>
                        @foreach (\App\Support\Duration::options() as $opt)
                            <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Max. Datenverlust') }}</flux:label>
                    <flux:description>
                        {{ __('Wieviel Datenverlust ist im Notfall verkraftbar?') }}
                        <span class="text-zinc-400">· {{ __('Fachbegriff: RPO') }}</span>
                    </flux:description>
                    <flux:select wire:model="rpo_minutes">
                        <flux:select.option value="">{{ __('Nicht definiert') }}</flux:select.option>
                        @foreach (\App\Support\Duration::options() as $opt)
                            <flux:select.option value="{{ $opt['value'] }}">{{ $opt['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            @if ($this->providers->isNotEmpty())
                <flux:field>
                    <flux:label>{{ __('Dienstleister') }}</flux:label>
                    <flux:description>{{ __('Wer ist für dieses System zuständig, wenn es ausfällt?') }}</flux:description>
                    <div class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($this->providers as $provider)
                            <flux:checkbox
                                wire:model="service_provider_ids"
                                value="{{ $provider->id }}"
                                :label="$provider->name.($provider->hotline ? ' · '.$provider->hotline : '')"
                            />
                        @endforeach
                    </div>
                </flux:field>
            @endif

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

    <flux:modal name="system-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('System löschen?') }}</flux:heading>
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

    <flux:modal name="system-import" class="max-w-2xl">
        <form wire:submit="import" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Systeme importieren') }}</flux:heading>
                <flux:subheading>
                    {{ __('JSON-Datei hochladen oder Inhalt einfügen. Bereits vorhandene Systeme (gleicher Name) werden übersprungen.') }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('JSON-Datei') }}</flux:label>
                <input type="file" wire:model="importFile" accept=".json,application/json,text/plain"
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border file:border-zinc-200 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-zinc-50 dark:file:border-zinc-700 dark:file:bg-zinc-800 dark:hover:file:bg-zinc-700">
                @error('importFile') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <div class="relative flex items-center">
                <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
                <span class="mx-3 text-xs uppercase text-zinc-400">{{ __('oder') }}</span>
                <div class="flex-grow border-t border-zinc-200 dark:border-zinc-700"></div>
            </div>

            <flux:field>
                <flux:label>{{ __('JSON einfügen') }}</flux:label>
                <flux:textarea
                    wire:model="importJson"
                    rows="6"
                    placeholder='{&#10;  "version": 1,&#10;  "systems": [&#10;    { "name": "SCADA", "category": "basisbetrieb", "priority": "Kritisch", "rto_minutes": 60, "rpo_minutes": 15 }&#10;  ]&#10;}'
                />
                @error('importJson') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <div class="rounded-md bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                <div class="mb-1 font-medium">{{ __('Erwartetes Format') }}</div>
                <div>
                    {{ __('Liste von Objekten mit Feldern') }}:
                    <code class="text-[11px]">name</code>,
                    <code class="text-[11px]">category</code> ({{ implode(', ', array_column(\App\Enums\SystemCategory::cases(), 'value')) }}),
                    <code class="text-[11px]">priority</code> ({{ __('optional, Name der Priorität') }}),
                    <code class="text-[11px]">rto_minutes</code>,
                    <code class="text-[11px]">rpo_minutes</code>,
                    <code class="text-[11px]">description</code>.
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" icon="arrow-up-tray">
                    {{ __('Importieren') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="system-template" class="max-w-xl">
        <form wire:submit="loadTemplate" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Vorlage laden') }}</flux:heading>
                <flux:subheading>
                    {{ __('Wählen Sie eine Branche – typische Systeme werden mit sinnvollen Prioritäten und RTO/RPO-Werten hinzugefügt. Bereits vorhandene Einträge werden übersprungen.') }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Branche') }}</flux:label>
                <flux:select wire:model.live="templateKey" required>
                    @foreach ($this->templateCatalog() as $key => $tpl)
                        <flux:select.option value="{{ $key }}">
                            {{ $tpl['label'] }} ({{ $tpl['count'] }} {{ __('Systeme') }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @if ($templateKey && isset($this->templateCatalog()[$templateKey]))
                    <flux:description class="mt-2">{{ $this->templateCatalog()[$templateKey]['hint'] }}</flux:description>
                @endif
            </flux:field>

            <div class="rounded-md bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                {{ __('Hinweis: Kategorien (Basis-, Geschäfts- und Unterstützende Systeme) und Prioritäten (Kritisch/Hoch/Normal) werden automatisch gesetzt. Sie können alles später anpassen.') }}
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" icon="sparkles">
                    {{ __('Systeme hinzufügen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
