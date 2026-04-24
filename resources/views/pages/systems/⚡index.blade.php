<?php

use App\Enums\SystemCategory;
use App\Models\System;
use App\Support\IndustryTemplates;
use App\Support\SystemImport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Systeme')] class extends Component {
    use WithFileUploads;

    public ?string $deletingId = null;

    public string $templateKey = '';

    public $importFile = null;

    public string $importJson = '';

    public function mount(): void
    {
        $this->templateKey = IndustryTemplates::defaultFor(Auth::user()->currentCompany()?->industry) ?? '';
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * Systems grouped by their category. Keyed by the enum value.
     *
     * @return array<string, \Illuminate\Support\Collection<int, System>>
     */
    #[Computed]
    public function systemsByCategory(): array
    {
        $systems = System::with(['priority'])
            ->withCount([
                'tasks',
                'tasks as open_tasks_count' => fn ($q) => $q->whereNull('completed_at'),
            ])
            ->get()
            ->sort(function (System $a, System $b) {
                $prio = ($a->priority?->sort ?? PHP_INT_MAX) <=> ($b->priority?->sort ?? PHP_INT_MAX);

                return $prio !== 0 ? $prio : strcasecmp($a->name, $b->name);
            })
            ->values();

        $grouped = [];

        foreach (SystemCategory::cases() as $category) {
            $grouped[$category->value] = $systems->where('category', $category)->values();
        }

        return $grouped;
    }

    public function confirmDelete(string $id): void
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
                    'downtime_cost_per_hour' => $entry['downtime_cost_per_hour'] ?? null,
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

<section class="w-full">
    <div class="mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Systeme & Betriebskontinuität') }}</flux:heading>
                <flux:subheading>
                    {{ __('Welche Systeme braucht Ihr Betrieb – und in welcher Reihenfolge müssen sie im Ernstfall zurück ans Netz?') }}
                </flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus" :href="$this->hasCompany ? route('systems.create') : null" :disabled="! $this->hasCompany" wire:navigate>
                {{ __('Neues System') }}
            </flux:button>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <flux:button size="sm" variant="filled" icon="sparkles" wire:click="openTemplate" :disabled="! $this->hasCompany">
                {{ __('Vorlage laden') }}
            </flux:button>
            <flux:button size="sm" variant="filled" icon="arrow-up-tray" wire:click="openImport" :disabled="! $this->hasCompany">
                {{ __('Importieren') }}
            </flux:button>
            <flux:button size="sm" variant="filled" icon="arrow-down-tray" :href="$this->hasCompany ? route('systems.export') : null" :disabled="! $this->hasCompany">
                {{ __('Exportieren') }}
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
                    <flux:button size="sm" variant="primary" icon="plus" :href="$this->hasCompany ? route('systems.create', ['category' => $category->value]) : null" :disabled="! $this->hasCompany" wire:navigate>
                        {{ __($category->label().' hinzufügen') }}
                    </flux:button>
                </div>

                @if ($systems->isEmpty())
                    <div class="px-5 py-8 text-center">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Noch kein System in dieser Kategorie.') }}
                        </flux:text>
                    </div>
                @else
                    <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($systems as $system)
                            <div class="flex flex-col rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('systems.show', ['system' => $system->id]) }}" wire:navigate class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="truncate font-medium text-zinc-900 hover:underline dark:text-white">{{ $system->name }}</span>
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
                                    </a>

                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" :href="route('systems.show', ['system' => $system->id])" wire:navigate>
                                                {{ __('Details') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="pencil" :href="route('systems.edit', ['system' => $system->id])" wire:navigate>
                                                {{ __('Bearbeiten') }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="qr-code" :href="route('systems.sticker', ['system' => $system->id])" target="_blank">
                                                {{ __('QR-Aushang') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $system->id }}')">
                                                {{ __('Löschen') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>

                                @if ($system->description)
                                    <flux:text class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $system->description }}
                                    </flux:text>
                                @endif

                                @if ($system->rto_minutes || $system->rpo_minutes || $system->downtime_cost_per_hour)
                                    <div class="mt-3 space-y-1.5 border-t border-zinc-100 pt-3 text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
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
                                        @if ($system->downtime_cost_per_hour)
                                            <div class="flex items-center gap-1.5">
                                                <flux:icon.currency-euro class="h-3.5 w-3.5 text-zinc-400" />
                                                <span>{{ __('Ausfallkosten') }}: <span class="font-medium">{{ number_format($system->downtime_cost_per_hour, 0, ',', '.') }} € / h</span></span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($system->tasks_count > 0)
                                    <div class="mt-3 flex items-center gap-1.5">
                                        <flux:icon.clipboard-document-list class="h-3.5 w-3.5 text-zinc-400" />
                                        <flux:badge color="zinc" size="sm">
                                            {{ $system->tasks_count }} {{ $system->tasks_count === 1 ? __('Aufgabe definiert') : __('Aufgaben definiert') }}
                                        </flux:badge>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

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
