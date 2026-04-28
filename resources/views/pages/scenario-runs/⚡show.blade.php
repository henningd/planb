<?php

use App\Events\ScenarioRunNoteUpdated;
use App\Events\ScenarioRunStepCompleted;
use App\Events\ScenarioRunStepReopened;
use App\Models\ScenarioRun;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title("Durchlauf")] class extends Component {
    public ScenarioRun $run;

    /** @var array<int, string> */
    public array $notes = [];

    /** @var array<int, array{id: int, name: string, initials: string}> */
    public array $presentUsers = [];

    /** @var array<string, true> */
    public array $recentlyChanged = [];

    public function mount(ScenarioRun $run): void
    {
        abort_if($run->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->run = $run->load(["steps", "startedBy", "scenario"]);

        foreach ($this->run->steps as $step) {
            $this->notes[$step->id] = (string) $step->note;
        }
    }

    #[Computed]
    public function progress(): array
    {
        $total = $this->run->steps->count();
        $done = $this->run->steps->whereNotNull("checked_at")->count();

        return [
            "done" => $done,
            "total" => $total,
            "percent" => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function toggleStep(string $stepId): void
    {
        $step = $this->run->steps->firstWhere("id", $stepId);

        abort_unless($step, 404);

        if ($step->checked_at) {
            $step->update(["checked_at" => null, "checked_by_user_id" => null]);
            $step->refresh();

            event(new ScenarioRunStepReopened($step, Auth::user()->name));
        } else {
            $step->update([
                "checked_at" => now(),
                "checked_by_user_id" => Auth::id(),
            ]);
            $step->refresh();

            event(new ScenarioRunStepCompleted(
                $step,
                Auth::user()->name,
                $step->checked_at?->toIso8601String(),
            ));
        }

        $this->run->load("steps");
    }

    public function saveNote(string $stepId): void
    {
        $step = $this->run->steps->firstWhere("id", $stepId);

        abort_unless($step, 404);

        $note = $this->notes[$stepId] ?? null;
        $step->update(["note" => $note]);

        event(new ScenarioRunNoteUpdated($step, Auth::user()->name, $note));

        Flux::toast(text: __("Notiz gespeichert."));
    }

    public function complete(): void
    {
        if (! $this->run->isActive()) {
            return;
        }

        $this->run->update(["ended_at" => now()]);
        $this->run->refresh();

        Flux::toast(variant: "success", text: __("Durchlauf abgeschlossen."));
    }

    public function abort(): void
    {
        if (! $this->run->isActive()) {
            return;
        }

        $this->run->update(["aborted_at" => now()]);
        $this->run->refresh();

        Flux::modal("run-abort")->close();
        Flux::toast(text: __("Durchlauf abgebrochen."));
    }

    /**
     * @param  array<int, array{id: int, name: string, initials: string}>  $users
     */
    #[On("echo-presence:scenario-run.{run.id}.presence,here")]
    public function setPresent(array $users): void
    {
        $this->presentUsers = array_values($users);
    }

    /**
     * @param  array{id: int, name: string, initials: string}  $user
     */
    #[On("echo-presence:scenario-run.{run.id}.presence,joining")]
    public function addPresent(array $user): void
    {
        foreach ($this->presentUsers as $existing) {
            if ((int) $existing["id"] === (int) $user["id"]) {
                return;
            }
        }
        $this->presentUsers[] = $user;
    }

    /**
     * @param  array{id: int, name: string, initials: string}  $user
     */
    #[On("echo-presence:scenario-run.{run.id}.presence,leaving")]
    public function removePresent(array $user): void
    {
        $this->presentUsers = array_values(array_filter(
            $this->presentUsers,
            fn (array $u): bool => (int) $u["id"] !== (int) $user["id"],
        ));
    }

    /**
     * @param  array{step_id: string, user_name: string, completed_at: ?string}  $payload
     */
    #[On("echo-private:scenario-run.{run.id},.step.completed")]
    public function applyStepCompleted(array $payload): void
    {
        $this->run->load("steps");
        $this->recentlyChanged[$payload["step_id"]] = true;
    }

    /**
     * @param  array{step_id: string, user_name: string}  $payload
     */
    #[On("echo-private:scenario-run.{run.id},.step.reopened")]
    public function applyStepReopened(array $payload): void
    {
        $this->run->load("steps");
        $this->recentlyChanged[$payload["step_id"]] = true;
    }

    /**
     * @param  array{step_id: string, user_name: string, note: ?string}  $payload
     */
    #[On("echo-private:scenario-run.{run.id},.note.updated")]
    public function applyNoteUpdated(array $payload): void
    {
        $stepId = $payload["step_id"];
        $this->notes[$stepId] = (string) ($payload["note"] ?? "");
        $this->run->load("steps");
    }
}; ?>


<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('scenario-runs.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Durchläufe') }}
        </flux:link>
    </div>

    @if ($run->isActive())
        <div class="mb-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-emerald-950">
            <div class="flex h-2 w-2 shrink-0 animate-pulse rounded-full bg-emerald-500"></div>
            <div class="flex-1">
                <span class="font-medium text-emerald-900 dark:text-emerald-100">
                    {{ __('Anwesend') }}
                    (<span x-text="$wire.presentUsers.length">{{ count($presentUsers) }}</span>):
                </span>
                <span class="text-emerald-800 dark:text-emerald-200">
                    @if (count($presentUsers) === 0)
                        <em class="opacity-70">{{ __('Verbinde…') }}</em>
                    @endif
                    <template x-for="user in $wire.presentUsers" :key="user.id">
                        <span class="ml-1 inline-flex items-center gap-1">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-200 text-xs font-semibold text-emerald-900 dark:bg-emerald-800 dark:text-emerald-100" x-text="user.initials"></span>
                            <span x-text="user.name"></span>
                        </span>
                    </template>
                </span>
            </div>
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <flux:badge :color="$run->mode->color()">{{ $run->mode->label() }}</flux:badge>
                    @if ($run->isActive())
                        <flux:badge color="emerald">{{ __('Aktiv') }}</flux:badge>
                    @elseif ($run->aborted_at)
                        <flux:badge color="zinc">{{ __('Abgebrochen') }}</flux:badge>
                    @else
                        <flux:badge color="zinc">{{ __('Abgeschlossen') }}</flux:badge>
                    @endif
                </div>
                <flux:heading size="xl" class="mt-2">{{ $run->title }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Gestartet') }}: {{ $run->started_at->format('d.m.Y H:i') }}
                    @if ($run->startedBy) · {{ $run->startedBy->name }} @endif
                </flux:text>
            </div>

            @if ($run->isActive())
                <div class="flex items-center gap-2">
                    <flux:button type="button" variant="ghost" wire:click="$dispatch('open-modal', 'run-abort')" x-on:click="$dispatch('open-modal', 'run-abort')">
                        {{ __('Abbrechen') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" icon="check" wire:click="complete">
                        {{ __('Durchlauf abschließen') }}
                    </flux:button>
                </div>
            @endif
        </div>

        <div class="mt-5">
            <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Fortschritt') }}</span>
                <span>{{ $this->progress['done'] }} / {{ $this->progress['total'] }}</span>
            </div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div class="h-full bg-emerald-500 transition-all duration-500" style="width: {{ $this->progress['percent'] }}%"></div>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        @foreach ($run->steps as $step)
            <div
                wire:key="step-{{ $step->id }}"
                @class([
                    'rounded-xl border bg-white p-5 transition-colors duration-700 dark:bg-zinc-900',
                    'opacity-75' => $step->checked_at,
                    'border-emerald-300 dark:border-emerald-700' => isset($recentlyChanged[$step->id]),
                    'border-zinc-200 dark:border-zinc-700' => ! isset($recentlyChanged[$step->id]),
                ])
            >
                <div class="flex items-start gap-4">
                    <button
                        type="button"
                        wire:click="toggleStep('{{ $step->id }}')"
                        @class([
                            'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md border-2 transition',
                            'border-emerald-500 bg-emerald-500 text-white' => $step->checked_at,
                            'border-zinc-300 hover:border-emerald-500 dark:border-zinc-600' => ! $step->checked_at,
                        ])
                    >
                        @if ($step->checked_at)
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.42L8 12.59l7.29-7.3a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>

                    <div class="flex-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm text-zinc-500">{{ $step->sort }}.</span>
                            <span class="font-medium {{ $step->checked_at ? 'line-through' : '' }}">{{ $step->title }}</span>
                        </div>
                        @if ($step->description)
                            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $step->description }}</flux:text>
                        @endif
                        @if ($step->responsible)
                            <div class="mt-2">
                                <flux:badge color="zinc" size="sm">{{ __('Wer') }}: {{ $step->responsible }}</flux:badge>
                            </div>
                        @endif
                        @if ($step->checked_at)
                            <flux:text class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">
                                ✓ {{ $step->checked_at->format('d.m.Y H:i') }}
                                @if ($step->checkedBy) · {{ $step->checkedBy->name }} @endif
                            </flux:text>
                        @endif

                        <div class="mt-3">
                            <flux:textarea
                                wire:model="notes.{{ $step->id }}"
                                rows="2"
                                placeholder="{{ __('Notiz zu diesem Schritt…') }}"
                            />
                            <div class="mt-2 flex justify-end">
                                <flux:button type="button" size="sm" variant="ghost" wire:click="saveNote('{{ $step->id }}')">
                                    {{ __('Notiz speichern') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($run->ended_at || $run->aborted_at)
        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-5 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
            {{ __('Dieser Durchlauf ist abgeschlossen. Änderungen sind nicht mehr möglich, das Protokoll bleibt erhalten.') }}
        </div>
    @endif

    <flux:modal name="run-abort" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Durchlauf abbrechen?') }}</flux:heading>
                <flux:subheading>{{ __('Das Protokoll wird als abgebrochen markiert und bleibt einsehbar.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">{{ __('Zurück') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="abort">{{ __('Abbrechen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
