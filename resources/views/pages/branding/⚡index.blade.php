<?php

use App\Models\Company;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Branding')] class extends Component {
    use WithFileUploads;

    public string $display_name = '';

    public string $primary_color = '';

    /**
     * Hochgeladenes Logo (Livewire-Datei). Wird beim save() in den Storage geschrieben.
     */
    public $logo;

    public function mount(): void
    {
        $company = Auth::user()->currentCompany();
        if ($company === null) {
            return;
        }

        $this->display_name = (string) $company->display_name;
        $this->primary_color = (string) $company->primary_color;
    }

    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()->currentCompany();
    }

    public function save(): void
    {
        $company = $this->company;
        if ($company === null) {
            Flux::toast(variant: 'warning', text: __('Kein Mandant aktiv.'));

            return;
        }

        $validated = $this->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'max:1024', 'mimes:png,svg,jpg,jpeg,webp'],
        ]);

        if ($this->logo !== null) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $path = $this->logo->store('company-logos/'.$company->id, 'public');
            $company->logo_path = $path;
        }

        $company->display_name = $validated['display_name'] ?: null;
        $company->primary_color = $validated['primary_color'] ?: null;
        $company->save();

        $this->logo = null;
        Flux::toast(variant: 'success', text: __('Branding gespeichert.'));
    }

    public function removeLogo(): void
    {
        $company = $this->company;
        if ($company === null || $company->logo_path === null) {
            return;
        }

        if (Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }
        $company->logo_path = null;
        $company->save();

        Flux::toast(variant: 'success', text: __('Logo entfernt.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">{{ __('Branding') }}</flux:heading>
        <flux:subheading>
            {{ __('Eigenes Logo, Anzeigename und Primärfarbe für diesen Mandanten. Wirkt im Sidebar-Header und im PDF-Handbuch.') }}
        </flux:subheading>
    </div>

    @if (! $this->company)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        <form wire:submit="save" class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Logo') }}</flux:heading>
                <flux:subheading class="mb-4">
                    {{ __('PNG, SVG, JPG oder WEBP, maximal 1 MB. Wird im Sidebar-Header und in jedem revisionssicheren PDF-Handbuch eingebunden.') }}
                </flux:subheading>

                @if ($this->company->logo_path)
                    <div class="mb-4 flex items-center gap-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <img
                            src="{{ $this->company->logoUrl() }}"
                            alt="Logo"
                            class="size-16 rounded-md bg-white object-contain p-1 ring-1 ring-zinc-200 dark:ring-zinc-700"
                        />
                        <div class="flex-1">
                            <flux:text class="text-sm">{{ __('Aktuelles Logo') }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ basename($this->company->logo_path) }}</flux:text>
                        </div>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="removeLogo"
                            wire:confirm="{{ __('Logo wirklich entfernen?') }}"
                        >
                            {{ __('Entfernen') }}
                        </flux:button>
                    </div>
                @endif

                <flux:input
                    type="file"
                    accept="image/png,image/svg+xml,image/jpeg,image/webp"
                    wire:model="logo"
                    :label="$this->company->logo_path ? __('Logo ersetzen') : __('Logo hochladen')"
                />

                @if ($logo)
                    <flux:text class="mt-2 text-xs text-emerald-600">
                        {{ __('Bereit zum Speichern:') }} {{ $logo->getClientOriginalName() }}
                    </flux:text>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Anzeigename') }}</flux:heading>
                <flux:subheading class="mb-4">
                    {{ __('Optionaler Name für die Sidebar und PDF-Footer. Leer = es wird der Firmenname „:name" verwendet.', ['name' => $this->company->name]) }}
                </flux:subheading>
                <flux:input wire:model="display_name" placeholder="{{ $this->company->name }}" />
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">{{ __('Primärfarbe') }}</flux:heading>
                <flux:subheading class="mb-4">
                    {{ __('Hex-Code für Akzentelemente in PDF-Headern und einem CSS-Custom-Property im Layout. Format: #rrggbb.') }}
                </flux:subheading>
                <div class="flex items-center gap-3">
                    <input
                        type="color"
                        wire:model.live="primary_color"
                        value="{{ $primary_color ?: '#4f46e5' }}"
                        class="h-10 w-16 cursor-pointer rounded border border-zinc-200 dark:border-zinc-700"
                    />
                    <flux:input wire:model="primary_color" placeholder="#4f46e5" class="font-mono" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button type="submit" variant="primary" icon="check">{{ __('Speichern') }}</flux:button>
            </div>
        </form>
    @endif
</section>
