<?php

use App\Models\AiSystem;
use App\Support\Ai\AiRiskClassifier;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('KI-Klassifizierung')] class extends Component {
    public bool $prohibited = false;

    public bool $high_risk_area = false;

    public bool $safety_component = false;

    public bool $transparency = false;

    public string $systemName = '';

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function result(): \App\Enums\AiRiskClass
    {
        return AiRiskClassifier::classify([
            'prohibited' => $this->prohibited,
            'high_risk_area' => $this->high_risk_area,
            'safety_component' => $this->safety_component,
            'transparency' => $this->transparency,
        ]);
    }

    public function saveAsSystem(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'systemName' => ['required', 'string', 'max:255'],
        ]);

        $system = AiSystem::create([
            'name' => $validated['systemName'],
            'risk_class' => $this->result->value,
        ]);

        $this->redirectRoute('ai-systems.show', ['aiSystem' => $system->id], navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-2">
        <flux:link :href="route('ai-systems.index')" wire:navigate class="text-sm">
            ← {{ __('Alle KI-Systeme') }}
        </flux:link>
    </div>

    <div class="mb-6">
        <flux:heading size="xl">{{ __('KI-Klassifizierung (EU-KI-VO)') }}</flux:heading>
        <flux:subheading>
            {{ __('Beantworten Sie die Fragen von oben nach unten — die erste zutreffende Stufe bestimmt die Einordnung. Ersetzt keine Rechtsberatung, ist aber ein belastbarer Startpunkt.') }}
        </flux:subheading>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-rose-200 bg-white p-5 dark:border-rose-900 dark:bg-zinc-900">
            <flux:checkbox wire:model.live="prohibited" :label="__('1. Verbotene Praktik (Art. 5)?')" />
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('z. B. Social Scoring, manipulative Techniken, Emotionserkennung am Arbeitsplatz/in Bildung, biometrische Kategorisierung nach sensiblen Merkmalen, ungezieltes Gesichts-Scraping, Echtzeit-Fernidentifizierung im öffentlichen Raum.') }}</flux:text>
        </div>

        <div class="rounded-xl border border-red-200 bg-white p-5 dark:border-red-900 dark:bg-zinc-900">
            <flux:checkbox wire:model.live="high_risk_area" :label="__('2. Einsatz in einem Hochrisiko-Bereich (Annex III)?')" />
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Biometrie, kritische Infrastruktur, Bildung, Beschäftigung/Personalauswahl, wesentliche Dienste (Kreditwürdigkeit, Versicherung), Strafverfolgung, Migration/Asyl, Justiz.') }}</flux:text>
        </div>

        <div class="rounded-xl border border-red-200 bg-white p-5 dark:border-red-900 dark:bg-zinc-900">
            <flux:checkbox wire:model.live="safety_component" :label="__('2b. Sicherheitsbauteil eines regulierten Produkts (Annex I)?')" />
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('z. B. KI in Maschinen, Medizinprodukten, Fahrzeugen mit eigener EU-Produktsicherheitsvorschrift.') }}</flux:text>
        </div>

        <div class="rounded-xl border border-amber-200 bg-white p-5 dark:border-amber-900 dark:bg-zinc-900">
            <flux:checkbox wire:model.live="transparency" :label="__('3. Interaktion mit Menschen oder Erzeugung synthetischer Inhalte?')" />
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Chatbot, Text-/Bild-/Audio-/Video-Generierung, Deepfakes, (nicht verbotene) Emotionserkennung → Transparenzpflichten nach Art. 50.') }}</flux:text>
        </div>
    </div>

    <div class="mt-6 rounded-xl border-2 p-5 {{ match ($this->result->color()) {
        'rose' => 'border-rose-300 bg-rose-50 dark:border-rose-800 dark:bg-rose-950/30',
        'red' => 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30',
        'amber' => 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30',
        default => 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30',
    } }}">
        <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Ergebnis der Einstufung') }}</div>
        <div class="mt-1 flex items-center gap-2">
            <flux:heading size="lg">{{ $this->result->label() }}</flux:heading>
            <flux:badge :color="$this->result->color()" size="sm">{{ __('Risikoklasse') }}</flux:badge>
        </div>
        <flux:text class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">{{ $this->result->obligationHint() }}</flux:text>

        <form wire:submit="saveAsSystem" class="mt-4 flex flex-col gap-3 border-t border-black/5 pt-4 sm:flex-row sm:items-end dark:border-white/10">
            <flux:input wire:model="systemName" :label="__('Als KI-System übernehmen')" type="text" placeholder="Bezeichnung des Systems" class="flex-1" />
            <flux:button variant="primary" type="submit" icon="plus">{{ __('Ins Register aufnehmen') }}</flux:button>
        </form>
    </div>
</section>
