<?php

use App\Enums\BcmsStage;
use App\Models\MaturityAssessment;
use App\Support\Bcms\MaturityCatalog;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reifegrad (BSI 200-4)')] class extends Component {
    /**
     * Antworten je Frage-Key (no/partial/yes).
     *
     * @var array<string, string>
     */
    public array $answers = [];

    public string $notes = '';

    public bool $justSaved = false;

    public function mount(): void
    {
        $latest = $this->latest();

        foreach (MaturityCatalog::allKeys() as $key) {
            $this->answers[$key] = $latest?->answers[$key] ?? 'no';
        }
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * Das zuletzt gespeicherte Assessment der aktuellen Firma.
     */
    #[Computed]
    public function latest(): ?MaturityAssessment
    {
        if (! $this->hasCompany) {
            return null;
        }

        return MaturityAssessment::query()
            ->orderByDesc('assessed_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * @return array<int, array{title: string, questions: array<int, array{key: string, text: string}>}>
     */
    #[Computed]
    public function dimensions(): array
    {
        return MaturityCatalog::dimensions();
    }

    /**
     * Lückenliste: alle Fragen, die mit „Nein“ oder „Teilweise“ beantwortet wurden.
     *
     * @return array<int, array{text: string, answer: string}>
     */
    #[Computed]
    public function gaps(): array
    {
        $gaps = [];

        foreach ($this->dimensions() as $dimension) {
            foreach ($dimension['questions'] as $question) {
                $answer = $this->answers[$question['key']] ?? 'no';

                if ($answer !== 'yes') {
                    $gaps[] = ['text' => $question['text'], 'answer' => $answer];
                }
            }
        }

        return $gaps;
    }

    public function evaluate(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $allowed = array_keys(MaturityCatalog::ANSWER_SCORES);

        $score = 0;
        foreach (MaturityCatalog::allKeys() as $key) {
            $answer = $this->answers[$key] ?? 'no';

            if (! in_array($answer, $allowed, true)) {
                $answer = 'no';
                $this->answers[$key] = $answer;
            }

            $score += MaturityCatalog::ANSWER_SCORES[$answer];
        }

        $max = MaturityCatalog::maxScore();
        $stage = MaturityCatalog::stageForScore($score, $max);

        MaturityAssessment::create([
            'answers' => $this->answers,
            'score' => $score,
            'stage' => $stage,
            'assessed_at' => CarbonImmutable::now()->toDateString(),
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);

        $this->justSaved = true;
        unset($this->latest, $this->gaps);

        Flux::toast(variant: 'success', text: __('Reifegrad ausgewertet und gespeichert.'));
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

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Reifegrad-Self-Assessment') }}</flux:heading>
        <flux:subheading>
            {{ __('Bestimmen Sie die Reifegradstufe Ihres BCMS nach dem BSI-200-4-Stufenmodell (Reaktiv-, Aufbau- oder Standard-BCMS).') }}
        </flux:subheading>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    @if ($this->hasCompany)
        @php($current = $this->latest)

        @if ($current?->stage)
            <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <flux:badge :color="$current->stage->color()" size="lg" class="text-base">
                            {{ $current->stage->label() }}
                        </flux:badge>
                        <div>
                            <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $current->stage->description() }}</flux:text>
                            <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                                {{ __('Zuletzt bewertet am') }}: {{ $current->assessed_at?->format('d.m.Y') }}
                                ·
                                {{ __('Punkte') }}: {{ $current->score }} / {{ \App\Support\Bcms\MaturityCatalog::maxScore() }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($justSaved && count($this->gaps) > 0)
            <div class="mb-8 rounded-xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-950">
                <flux:heading size="base" class="text-amber-900 dark:text-amber-100">{{ __('Lückenliste') }}</flux:heading>
                <flux:subheading class="text-amber-800 dark:text-amber-200">
                    {{ __('Diese Punkte sind noch offen und sollten als Nächstes angegangen werden.') }}
                </flux:subheading>
                <ul class="mt-4 space-y-2">
                    @foreach ($this->gaps as $gap)
                        <li class="flex items-start gap-2 text-sm text-amber-900 dark:text-amber-100">
                            <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
                            <span>
                                {{ $gap['text'] }}
                                <flux:badge size="sm" :color="$gap['answer'] === 'partial' ? 'amber' : 'rose'" class="ml-1">
                                    {{ $gap['answer'] === 'partial' ? __('Teilweise') : __('Nein') }}
                                </flux:badge>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form wire:submit="evaluate" class="space-y-6">
            @foreach ($this->dimensions as $dimension)
                <div wire:key="dimension-{{ $loop->index }}" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ $dimension['title'] }}</flux:heading>

                    <div class="mt-4 space-y-5">
                        @foreach ($dimension['questions'] as $question)
                            <div wire:key="question-{{ $question['key'] }}" class="grid gap-2 sm:grid-cols-3 sm:items-center sm:gap-4">
                                <flux:text class="sm:col-span-2">{{ $question['text'] }}</flux:text>
                                <flux:select wire:model="answers.{{ $question['key'] }}">
                                    @foreach ($this->answerOptions() as $option)
                                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <flux:textarea wire:model="notes" :label="__('Notizen (optional)')" rows="2" placeholder="z. B. Kontext, geplante Maßnahmen, Zuständige" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:button variant="primary" icon="clipboard-document-check" type="submit">
                    {{ __('Auswerten & speichern') }}
                </flux:button>
            </div>
        </form>
    @endif
</section>
