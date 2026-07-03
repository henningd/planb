<?php

use App\Enums\Nis2Readiness;
use App\Mail\Nis2QuickCheckConfirmation;
use App\Models\Lead;
use App\Support\Marketing\Nis2QuickCheckCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * Antworten je Frage-Key (no/partial/yes).
     *
     * @var array<string, string>
     */
    public array $answers = [];

    /** Aktueller Schritt des Assistenten: quiz | result | done. */
    public string $step = 'quiz';

    // Lead-Formular
    public string $email = '';

    public string $companyName = '';

    public string $contactName = '';

    public bool $consentReport = false;

    public bool $consentMarketing = false;

    /** Honeypot gegen Bots – muss leer bleiben. */
    public string $website = '';

    public function mount(): void
    {
        foreach (Nis2QuickCheckCatalog::allKeys() as $key) {
            $this->answers[$key] = 'no';
        }
    }

    /**
     * @return array<int, array{key: string, title: string, recommendation: string, questions: array<int, array{key: string, text: string}>}>
     */
    #[Computed]
    public function dimensions(): array
    {
        return Nis2QuickCheckCatalog::dimensions();
    }

    #[Computed]
    public function score(): int
    {
        return Nis2QuickCheckCatalog::scoreFor($this->answers);
    }

    #[Computed]
    public function maxScore(): int
    {
        return Nis2QuickCheckCatalog::maxScore();
    }

    #[Computed]
    public function percent(): int
    {
        $max = $this->maxScore();

        return $max > 0 ? (int) round($this->score / $max * 100) : 0;
    }

    #[Computed]
    public function readiness(): Nis2Readiness
    {
        return Nis2QuickCheckCatalog::readinessForScore($this->score, $this->maxScore());
    }

    /**
     * Handlungsfelder, in denen noch mindestens eine Frage nicht mit „Ja“
     * beantwortet wurde – samt zugehörigem Empfehlungstext.
     *
     * @return array<int, array{key: string, title: string, recommendation: string}>
     */
    #[Computed]
    public function openRecommendations(): array
    {
        return Nis2QuickCheckCatalog::openRecommendations($this->answers);
    }

    public function showResult(): void
    {
        $this->step = 'result';
    }

    public function backToQuiz(): void
    {
        $this->step = 'quiz';
    }

    public function submit(): void
    {
        // Honeypot: von Bots ausgefülltes Feld → still verwerfen.
        if ($this->website !== '') {
            return;
        }

        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'consentReport' => ['accepted'],
        ], attributes: [
            'email' => __('E-Mail-Adresse'),
            'consentReport' => __('Einwilligung'),
        ]);

        $lead = Lead::create([
            'email' => $validated['email'],
            'company_name' => $this->companyName !== '' ? $this->companyName : null,
            'contact_name' => $this->contactName !== '' ? $this->contactName : null,
            'source' => 'nis2-quick-check',
            'answers' => $this->answers,
            'score' => $this->score,
            'readiness' => $this->readiness,
            'consent_marketing' => $this->consentMarketing,
            'consent_at' => Carbon::now(),
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
        ]);

        // Double-Opt-In: Bestätigungs-E-Mail mit signiertem Link. Die
        // detaillierte PDF-Auswertung wird erst nach Bestätigung versendet.
        Mail::to($lead->email)->send(new Nis2QuickCheckConfirmation($lead));

        $this->step = 'done';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function answerOptions(): array
    {
        return [
            ['value' => 'no', 'label' => __('Nein')],
            ['value' => 'partial', 'label' => __('Teilweise')],
            ['value' => 'yes', 'label' => __('Ja')],
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-3xl">
    {{-- Score-Anzeige: im Quiz live, im Ergebnis als Auswertung --}}
    @if ($step !== 'done')
        <div class="sticky top-0 z-10 mb-8 rounded-2xl border border-zinc-200 bg-white/90 p-5 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/90">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="base">{{ __('Ihr NIS2-Reifegrad') }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ $this->score }} / {{ $this->maxScore }} {{ __('Punkte') }}
                    </flux:text>
                </div>
                <flux:badge :color="$this->readiness->color()" size="lg">{{ $this->readiness->label() }}</flux:badge>
            </div>
            <div class="mt-4 h-2.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div
                    class="h-full rounded-full transition-all duration-500 @class([
                        'bg-rose-500' => $this->readiness === Nis2Readiness::Kritisch,
                        'bg-amber-500' => $this->readiness === Nis2Readiness::Aufbau,
                        'bg-emerald-500' => $this->readiness === Nis2Readiness::Solide,
                    ])"
                    style="width: {{ max($this->percent, 3) }}%"
                ></div>
            </div>
        </div>
    @endif

    {{-- Schritt 1: Fragen --}}
    @if ($step === 'quiz')
        <div class="space-y-6">
            @foreach ($this->dimensions as $dimension)
                <div wire:key="dim-{{ $dimension['key'] }}" class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ $dimension['title'] }}</flux:heading>

                    <div class="mt-5 space-y-6">
                        @foreach ($dimension['questions'] as $question)
                            <div wire:key="q-{{ $question['key'] }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <flux:text class="sm:max-w-md">{{ $question['text'] }}</flux:text>
                                <flux:radio.group wire:model.live="answers.{{ $question['key'] }}" variant="segmented" size="sm">
                                    @foreach ($this->answerOptions() as $option)
                                        <flux:radio value="{{ $option['value'] }}" label="{{ $option['label'] }}" />
                                    @endforeach
                                </flux:radio.group>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <flux:button variant="primary" icon="arrow-right" wire:click="showResult">
                    {{ __('Ergebnis anzeigen') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Schritt 2: Ergebnis + Lead-Formular --}}
    @if ($step === 'result')
        <div class="space-y-6">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $this->readiness->description() }}</flux:text>
            </div>

            @if (count($this->openRecommendations) > 0)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-950">
                    <flux:heading size="base" class="text-amber-900 dark:text-amber-100">{{ __('Ihre wichtigsten Handlungsfelder') }}</flux:heading>
                    <flux:text size="sm" class="mt-1 text-amber-800 dark:text-amber-200">
                        {{ __('In der ausführlichen Auswertung erhalten Sie zu jedem Punkt konkrete nächste Schritte.') }}
                    </flux:text>
                    <ul class="mt-4 space-y-3">
                        @foreach ($this->openRecommendations as $item)
                            <li wire:key="rec-{{ $loop->index }}" class="flex items-start gap-2 text-sm text-amber-900 dark:text-amber-100">
                                <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                                <span><strong>{{ $item['title'] }}:</strong> {{ $item['recommendation'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Lead-Formular: Detail-Auswertung gegen E-Mail-Adresse --}}
            <form wire:submit="submit" class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Ausführliche Auswertung als PDF erhalten') }}</flux:heading>
                <flux:subheading>
                    {{ __('Wir senden Ihnen Ihr persönliches Ergebnis mit priorisierten Handlungsempfehlungen per E-Mail zu.') }}
                </flux:subheading>

                {{-- Honeypot --}}
                <flux:input wire:model="website" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="email" type="email" :label="__('E-Mail-Adresse')" placeholder="name@firma.de" required class="sm:col-span-2" />
                    <flux:input wire:model="companyName" :label="__('Unternehmen (optional)')" />
                    <flux:input wire:model="contactName" :label="__('Ihr Name (optional)')" />
                </div>

                <div class="mt-5 space-y-3">
                    <flux:checkbox wire:model="consentReport" :label="__('Ich möchte meine Auswertung per E-Mail erhalten und bestätige die Datenschutzhinweise. (erforderlich)')" />
                    <flux:checkbox wire:model="consentMarketing" :label="__('Zusätzlich möchte ich gelegentlich Praxis-Tipps zu NIS2 und Notfallvorsorge erhalten. (optional, jederzeit widerrufbar)')" />
                </div>

                <flux:text size="sm" class="mt-4 text-zinc-500 dark:text-zinc-400">
                    {{ __('Mit dem Absenden erhalten Sie zunächst eine Bestätigungs-E-Mail (Double-Opt-In). Details in der') }}
                    <flux:link href="/datenschutz" target="_blank">{{ __('Datenschutzerklärung') }}</flux:link>.
                </flux:text>

                <div class="mt-6 flex items-center justify-between gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:button variant="ghost" icon="arrow-left" wire:click="backToQuiz" type="button">
                        {{ __('Antworten anpassen') }}
                    </flux:button>
                    <flux:button variant="primary" icon="envelope" type="submit">
                        {{ __('Auswertung anfordern') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Schritt 3: Danke --}}
    @if ($step === 'done')
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-8 text-center dark:border-emerald-700 dark:bg-emerald-950">
            <flux:icon.check-circle class="mx-auto h-12 w-12 text-emerald-600 dark:text-emerald-400" />
            <flux:heading size="xl" class="mt-4 text-emerald-900 dark:text-emerald-100">{{ __('Fast geschafft!') }}</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-md text-emerald-800 dark:text-emerald-200">
                {{ __('Bitte bestätigen Sie den Link in der E-Mail, die wir Ihnen soeben gesendet haben. Danach erhalten Sie Ihre ausführliche NIS2-Auswertung als PDF.') }}
            </flux:text>
        </div>
    @endif
</div>
