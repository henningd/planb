<?php

use App\Models\HandbookVersion;
use App\Models\IncidentReport;
use App\Models\LessonLearned;
use App\Models\ScenarioRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Neue Lessons-Learned-Auswertung')] class extends Component {
    public ?string $incident_report_id = null;

    public ?string $scenario_run_id = null;

    public ?string $handbook_version_id = null;

    public string $title = '';

    public string $root_cause = '';

    public string $what_went_well = '';

    public string $what_went_poorly = '';

    public string $bind_kind = 'none';

    public function mount(?string $incident = null, ?string $run = null): void
    {
        if ($incident) {
            $report = IncidentReport::find($incident);
            if ($report) {
                $this->incident_report_id = $report->id;
                $this->bind_kind = 'incident';
                $this->title = __('Auswertung zu :title', ['title' => $report->title]);
            }
        } elseif ($run) {
            $scenarioRun = ScenarioRun::find($run);
            if ($scenarioRun) {
                $this->scenario_run_id = $scenarioRun->id;
                $this->bind_kind = 'run';
                $this->title = __('Auswertung zu :title', ['title' => $scenarioRun->title]);
            }
        }
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    #[Computed]
    public function incidents()
    {
        return IncidentReport::orderByDesc('occurred_at')->limit(50)->get();
    }

    #[Computed]
    public function scenarioRuns()
    {
        return ScenarioRun::orderByDesc('started_at')->limit(50)->get();
    }

    #[Computed]
    public function handbookVersions()
    {
        return HandbookVersion::orderByDesc('changed_at')->limit(50)->get();
    }

    public function updatedBindKind(string $value): void
    {
        if ($value !== 'incident') {
            $this->incident_report_id = null;
        }
        if ($value !== 'run') {
            $this->scenario_run_id = null;
        }
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'root_cause' => ['nullable', 'string', 'max:5000'],
            'what_went_well' => ['nullable', 'string', 'max:5000'],
            'what_went_poorly' => ['nullable', 'string', 'max:5000'],
            'incident_report_id' => ['nullable', 'uuid', 'exists:incident_reports,id'],
            'scenario_run_id' => ['nullable', 'uuid', 'exists:scenario_runs,id'],
            'handbook_version_id' => ['nullable', 'uuid', 'exists:handbook_versions,id'],
            'bind_kind' => ['required', 'in:none,incident,run'],
        ]);

        if ($validated['bind_kind'] === 'incident' && empty($validated['incident_report_id'])) {
            $this->addError('incident_report_id', __('Bitte einen Vorfall auswählen.'));

            return;
        }
        if ($validated['bind_kind'] === 'run' && empty($validated['scenario_run_id'])) {
            $this->addError('scenario_run_id', __('Bitte eine Übung/Lage auswählen.'));

            return;
        }

        $lesson = DB::transaction(function () use ($validated) {
            return LessonLearned::create([
                'title' => $validated['title'],
                'root_cause' => $validated['root_cause'] ?: null,
                'what_went_well' => $validated['what_went_well'] ?: null,
                'what_went_poorly' => $validated['what_went_poorly'] ?: null,
                'incident_report_id' => $validated['bind_kind'] === 'incident' ? $validated['incident_report_id'] : null,
                'scenario_run_id' => $validated['bind_kind'] === 'run' ? $validated['scenario_run_id'] : null,
                'handbook_version_id' => $validated['handbook_version_id'] ?? null,
                'author_user_id' => Auth::id(),
            ]);
        });

        $this->redirectRoute('lessons-learned.show', ['lesson' => $lesson->id], navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Neue Lessons-Learned-Auswertung') }}</flux:heading>
        <flux:subheading>
            {{ __('Erfassen Sie eine Kurzanalyse — Ursache, was gut lief, was nicht. Maßnahmen pflegen Sie anschließend in der Detail-Ansicht.') }}
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="title" :label="__('Titel')" required placeholder="{{ __('z. B. Phishing-Welle Buchhaltung') }}" />

        <div>
            <flux:label>{{ __('Bezug') }}</flux:label>
            <flux:radio.group wire:model.live="bind_kind">
                <flux:radio value="none" :label="__('Frei (kein direkter Bezug)')" />
                <flux:radio value="incident" :label="__('Vorfall')" />
                <flux:radio value="run" :label="__('Übung/Lage')" />
            </flux:radio.group>
        </div>

        @if ($bind_kind === 'incident')
            <flux:select wire:model="incident_report_id" :label="__('Vorfall auswählen')" required>
                <flux:select.option value="">{{ __('— bitte wählen —') }}</flux:select.option>
                @foreach ($this->incidents as $incident)
                    <flux:select.option value="{{ $incident->id }}">
                        {{ $incident->occurred_at->format('d.m.Y') }} · {{ $incident->title }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if ($bind_kind === 'run')
            <flux:select wire:model="scenario_run_id" :label="__('Übung/Lage auswählen')" required>
                <flux:select.option value="">{{ __('— bitte wählen —') }}</flux:select.option>
                @foreach ($this->scenarioRuns as $run)
                    <flux:select.option value="{{ $run->id }}">
                        {{ $run->started_at->format('d.m.Y') }} · {{ $run->title }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        @endif

        @if ($this->handbookVersions->isNotEmpty())
            <flux:select wire:model="handbook_version_id" :label="__('Bezug auf Handbuch-Version (optional)')">
                <flux:select.option value="">{{ __('— keine —') }}</flux:select.option>
                @foreach ($this->handbookVersions as $version)
                    <flux:select.option value="{{ $version->id }}">
                        {{ __('Version') }} {{ $version->version }} · {{ $version->changed_at?->format('d.m.Y') }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:textarea wire:model="root_cause" :label="__('Ursache (Root Cause)')" rows="3" />
        <flux:textarea wire:model="what_went_well" :label="__('Was lief gut?')" rows="3" />
        <flux:textarea wire:model="what_went_poorly" :label="__('Was lief nicht gut?')" rows="3" />

        <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <flux:button type="button" variant="filled" :href="route('lessons-learned.index')" wire:navigate>
                {{ __('Abbrechen') }}
            </flux:button>
            <flux:button variant="primary" type="submit">
                {{ __('Speichern') }}
            </flux:button>
        </div>
    </form>
</section>
