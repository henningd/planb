<?php

use App\Support\Backup\BackupCatalog;
use App\Support\Backup\Importer;
use App\Support\Settings\CompanySetting;
use App\Support\Settings\SettingsCatalog;
use App\Support\Settings\SystemSetting;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Systemeinstellungen')] class extends Component {
    use WithFileUploads;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @var array<string, bool>
     */
    public array $overrides = [];

    /**
     * @var array<string, bool>
     */
    public array $exportAreas = [];

    /**
     * @var array<string, bool>
     */
    public array $importAreas = [];

    public $importFile = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $importPreview = null;

    public bool $importConfirming = false;

    /**
     * @var array<string, array{deleted?: int, inserted?: int, updated?: int}>|null
     */
    public ?array $importSummary = null;

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();
        if ($company === null) {
            return;
        }

        $tenant = CompanySetting::for($company);
        foreach (SettingsCatalog::byScope(SettingsCatalog::COMPANY) as $key => $def) {
            $this->overrides[$key] = $tenant->isOverridden($key);
            $this->values[$key] = $this->overrides[$key]
                ? $tenant->get($key)
                : SystemSetting::get($key, $def['default']);
        }

        // Standardmäßig sind alle Backup-Bereiche zum Export ausgewählt.
        foreach (array_keys(BackupCatalog::all()) as $key) {
            $this->exportAreas[$key] = true;
        }
    }

    public function save(): void
    {
        $company = Auth::user()->currentCompany();
        if ($company === null) {
            Flux::toast(variant: 'warning', text: __('Kein Mandant aktiv.'));

            return;
        }

        $tenant = CompanySetting::for($company);
        foreach (SettingsCatalog::byScope(SettingsCatalog::COMPANY) as $key => $def) {
            if (! empty($this->overrides[$key])) {
                $tenant->set($key, $this->values[$key] ?? $def['default']);
            } else {
                $tenant->unset($key);
            }
        }

        Flux::toast(variant: 'success', text: __('Mandanten-Einstellungen gespeichert.'));
    }

    /**
     * @return array<string, array{scope: string, type: string, default: mixed, label: string, description: string, enum?: array<string,string>, min?: int, max?: int}>
     */
    public function defs(): array
    {
        return SettingsCatalog::byScope(SettingsCatalog::COMPANY);
    }

    public function platformDefault(string $key): mixed
    {
        $def = SettingsCatalog::definition($key);

        return SystemSetting::get($key, $def['default'] ?? null);
    }

    public function formatValue(string $key, mixed $value): string
    {
        $def = SettingsCatalog::definition($key);
        if ($def === null) {
            return (string) $value;
        }

        return match ($def['type']) {
            'bool' => $value ? __('aktiv') : __('aus'),
            'enum' => $def['enum'][(string) $value] ?? (string) $value,
            default => (string) $value,
        };
    }

    /**
     * @return array<string, array{label: string, table: string, mode: string, order: int}>
     */
    public function backupAreas(): array
    {
        return BackupCatalog::all();
    }

    /**
     * Generiert die Download-URL für die aktuell ausgewählten Bereiche.
     */
    public function exportUrl(): string
    {
        $selected = collect($this->exportAreas)
            ->filter()
            ->keys()
            ->all();

        $team = Auth::user()->currentTeam?->slug ?? '';

        return route('system-settings.backup.download', ['current_team' => $team])
            .(empty($selected) ? '' : '?'.http_build_query(['areas' => $selected]));
    }

    /**
     * Liest das hochgeladene JSON ein, validiert die Grobstruktur und
     * preselectiert die Bereiche, die im File enthalten sind.
     */
    public function loadImportFile(): void
    {
        $this->importPreview = null;
        $this->importSummary = null;
        $this->importConfirming = false;
        $this->importAreas = [];

        if (! $this->importFile) {
            return;
        }

        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $data = json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Datei konnte nicht gelesen werden: :msg', ['msg' => $e->getMessage()]));
            $this->importFile = null;

            return;
        }

        if (! is_array($data) || ! isset($data['areas']) || ! is_array($data['areas'])) {
            Flux::toast(variant: 'danger', text: __('Datei ist kein gültiges PlanB-Backup (areas fehlt).'));
            $this->importFile = null;

            return;
        }

        $this->importPreview = $data;
        foreach (array_keys(BackupCatalog::all()) as $key) {
            $this->importAreas[$key] = isset($data['areas'][$key]);
        }
    }

    public function confirmImport(): void
    {
        if (! $this->importPreview) {
            return;
        }

        $selected = collect($this->importAreas)->filter()->keys();
        if ($selected->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('Bitte mindestens einen Bereich auswählen.'));

            return;
        }

        $this->importConfirming = true;
    }

    public function cancelImport(): void
    {
        $this->importConfirming = false;
    }

    public function runImport(): void
    {
        $company = Auth::user()->currentCompany();
        if (! $company || ! $this->importPreview) {
            return;
        }

        $selected = collect($this->importAreas)->filter()->keys()->all();

        try {
            $this->importSummary = Importer::import($company, $this->importPreview, $selected);
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Import fehlgeschlagen: :msg', ['msg' => $e->getMessage()]));

            return;
        }

        $this->importConfirming = false;
        $this->importPreview = null;
        $this->importFile = null;

        $totalIns = collect($this->importSummary)->sum(fn ($v) => $v['inserted'] ?? 0);
        $totalUpd = collect($this->importSummary)->sum(fn ($v) => $v['updated'] ?? 0);
        Flux::toast(variant: 'success', text: __(':ins Datensätze importiert, :upd aktualisiert.', ['ins' => $totalIns, 'upd' => $totalUpd]));
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Systemeinstellungen') }}</flux:heading>
        <flux:subheading>
            {{ __('Mandanten-spezifische Schalter. Jede Einstellung verwendet standardmäßig den Plattform-Default; aktivieren Sie „Eigener Wert", um sie für Ihren Mandanten zu überschreiben.') }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5">
        @foreach ($this->defs() as $key => $def)
            @php
                $platform = $this->platformDefault($key);
                $platformDisplay = $this->formatValue($key, $platform);
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                    <div class="min-w-0 flex-1">
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">
                            {{ __($def['label']) }}
                        </flux:text>
                        @if (! empty($def['description']))
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($def['description']) }}</flux:text>
                        @endif
                        <flux:text class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                            {{ __('Plattform-Default') }}: <span class="font-mono">{{ $platformDisplay }}</span>
                        </flux:text>
                    </div>
                    <div class="shrink-0">
                        <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="overrides.{{ $key }}" class="rounded border-zinc-300 dark:border-zinc-600">
                            {{ __('Eigener Wert') }}
                        </label>
                    </div>
                </div>
                @if (! empty($overrides[$key]))
                    <div class="px-5 py-4">
                        @include('partials.setting-field', ['key' => $key, 'def' => $def])
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <flux:button variant="primary" type="submit" icon="check">{{ __('Speichern') }}</flux:button>
        </div>
    </form>

    <div class="mt-12 grid gap-6 lg:grid-cols-2">
        {{-- Export --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Daten-Export') }}</flux:heading>
                <flux:subheading>{{ __('Wähle Bereiche und lade dir den aktuellen Stand als JSON-Datei herunter — nur Daten dieser Firma.') }}</flux:subheading>
            </div>
            <div class="space-y-2 p-5">
                @foreach ($this->backupAreas() as $key => $area)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="exportAreas.{{ $key }}" class="rounded border-zinc-300 dark:border-zinc-600">
                        {{ __($area['label']) }}
                    </label>
                @endforeach
            </div>
            <div class="flex items-center justify-end border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:button
                    variant="primary"
                    icon="arrow-down-tray"
                    :href="$this->exportUrl()"
                >
                    {{ __('Backup herunterladen') }}
                </flux:button>
            </div>
        </div>

        {{-- Import --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <flux:heading size="base">{{ __('Daten-Import') }}</flux:heading>
                <flux:subheading>
                    {{ __('Lade ein PlanB-Backup hoch und ersetze damit den aktuellen Bestand der gewählten Bereiche dieser Firma.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4 p-5">
                <flux:input
                    type="file"
                    accept="application/json,.json"
                    wire:model="importFile"
                    wire:change="loadImportFile"
                    :label="__('Backup-Datei (.json)')"
                />

                @if ($importPreview)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div><strong>{{ __('Quelle:') }}</strong> {{ $importPreview['company_name'] ?? '—' }}</div>
                        <div><strong>{{ __('Exportiert am:') }}</strong> {{ $importPreview['exported_at'] ?? '—' }}</div>
                    </div>

                    <div class="space-y-2">
                        @foreach ($this->backupAreas() as $key => $area)
                            @php($count = isset($importPreview['areas'][$key]) ? count($importPreview['areas'][$key]) : 0)
                            <label class="flex items-center justify-between gap-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        wire:model.live="importAreas.{{ $key }}"
                                        class="rounded border-zinc-300 dark:border-zinc-600"
                                        @disabled(! isset($importPreview['areas'][$key]))
                                    >
                                    <span @class(['text-zinc-400' => ! isset($importPreview['areas'][$key])])>
                                        {{ __($area['label']) }}
                                    </span>
                                </div>
                                @if (isset($importPreview['areas'][$key]))
                                    <flux:badge size="sm" color="zinc">{{ $count }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">{{ __('nicht im Backup') }}</flux:badge>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @if ($importConfirming)
                        <div class="rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
                            <strong>{{ __('Wirklich importieren?') }}</strong>
                            {{ __('Der aktuelle Bestand der gewählten Bereiche wird dabei vollständig ersetzt. Hängende Zuordnungen (Mitarbeiter ↔ Rolle, System ↔ Dienstleister …) werden mitentfernt und müssen nach dem Import neu gesetzt werden.') }}
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        @if ($importConfirming)
                            <flux:button variant="filled" type="button" wire:click="cancelImport">{{ __('Abbrechen') }}</flux:button>
                            <flux:button variant="danger" type="button" icon="arrow-up-tray" wire:click="runImport">
                                {{ __('Import jetzt durchführen') }}
                            </flux:button>
                        @else
                            <flux:button variant="primary" type="button" icon="arrow-up-tray" wire:click="confirmImport">
                                {{ __('Import vorbereiten') }}
                            </flux:button>
                        @endif
                    </div>
                @endif

                @if ($importSummary)
                    <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-xs dark:border-emerald-800 dark:bg-emerald-950/40">
                        <strong>{{ __('Import abgeschlossen') }}</strong>
                        <ul class="mt-2 space-y-0.5">
                            @foreach ($importSummary as $key => $stats)
                                <li>
                                    {{ __($this->backupAreas()[$key]['label'] ?? $key) }}:
                                    @if (isset($stats['inserted'])) {{ $stats['inserted'] }} {{ __('eingefügt') }} @endif
                                    @if (isset($stats['updated'])) {{ $stats['updated'] }} {{ __('aktualisiert') }} @endif
                                    @if (isset($stats['deleted'])) ({{ $stats['deleted'] }} {{ __('vorher gelöscht') }}) @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
