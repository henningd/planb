<?php

use App\Enums\OnboardingStep;
use App\Models\Company;
use App\Support\Onboarding\OnboardingProgress;
use App\Support\Onboarding\OnboardingService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Einrichtung')] class extends Component {
    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()->currentCompany();
    }

    #[Computed]
    public function progress(): ?OnboardingProgress
    {
        $company = $this->company;

        return $company ? OnboardingService::progressFor($company) : null;
    }

    public function focus(string $stepValue): void
    {
        $step = OnboardingStep::tryFrom($stepValue);
        if (! $step || ! $this->company) {
            return;
        }

        OnboardingService::resume($this->company);
        $this->redirectRoute($step->routeName(), [
            'current_team' => Auth::user()->currentTeam->slug,
        ], navigate: true);
    }

    public function markDone(string $stepValue): void
    {
        $step = OnboardingStep::tryFrom($stepValue);
        if (! $step || ! $this->company) {
            return;
        }
        OnboardingService::markStepCompleted($this->company, $step);
        unset($this->progress);
    }

    public function skip(string $stepValue): void
    {
        $step = OnboardingStep::tryFrom($stepValue);
        if (! $step || ! $step->isOptional() || ! $this->company) {
            return;
        }
        OnboardingService::skipStep($this->company, $step);
        unset($this->progress);
    }

    public function pause(): void
    {
        if (! $this->company) {
            return;
        }
        OnboardingService::pause($this->company);
        Flux::toast(variant: 'success', text: __('Einrichtung pausiert. Sie können jederzeit fortsetzen.'));
        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }

    public function dismiss(): void
    {
        if (! $this->company) {
            return;
        }
        OnboardingService::dismiss($this->company);
        Flux::toast(variant: 'success', text: __('Hinweis ausgeblendet. Wieder einblenden im Menü.'));
        $this->redirectRoute('dashboard', ['current_team' => Auth::user()->currentTeam->slug], navigate: true);
    }

    public function restart(): void
    {
        if (! $this->company) {
            return;
        }
        OnboardingService::restart($this->company);
        unset($this->progress);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    @if (! $this->company)
        <flux:heading size="xl">{{ __('Einrichtung') }}</flux:heading>
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @else
        @php($progress = $this->progress)
        <div>
            <flux:heading size="xl">{{ __('Einrichtung') }}</flux:heading>
            <flux:subheading>
                {{ __('Die wichtigsten Schritte, damit Ihr Notfallhandbuch tragfähig wird. Sie können jederzeit pausieren und später fortsetzen.') }}
            </flux:subheading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ $progress->doneSteps() }} / {{ $progress->totalSteps() }} {{ __('Schritten erledigt') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        @if ($progress->isFullyDone())
                            {{ __('Alle Pflichtschritte sind erledigt — sehr gut.') }}
                        @else
                            {{ __('Aktueller Schritt:') }} {{ $progress->nextStep()?->label() }}
                        @endif
                    </flux:text>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-semibold">{{ $progress->percentage() }} %</div>
                </div>
            </div>
            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div
                    class="h-full bg-emerald-500 transition-all"
                    style="width: {{ $progress->percentage() }}%"
                ></div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @if (! $progress->isFullyDone())
                    <flux:button size="sm" variant="filled" wire:click="pause" icon="pause">
                        {{ __('Pausieren') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="dismiss" icon="x-mark">
                        {{ __('Vom Dashboard ausblenden') }}
                    </flux:button>
                @else
                    <flux:button size="sm" variant="filled" wire:click="restart" icon="arrow-path">
                        {{ __('Erneut durchlaufen') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="space-y-3">
            @foreach ($progress->ordered() as $status)
                @php($step = $status->step)
                @php($isNext = $progress->nextStep()?->value === $step->value)
                <div @class([
                    'rounded-xl border bg-white p-5 dark:bg-zinc-900',
                    'border-emerald-300 dark:border-emerald-700' => $status->autoSatisfied || $status->manuallyCompleted,
                    'border-zinc-300 dark:border-zinc-600' => $status->manuallySkipped,
                    'border-sky-400 ring-2 ring-sky-100 dark:border-sky-700 dark:ring-sky-950' => $isNext,
                    'border-zinc-200 dark:border-zinc-700' => ! $status->isDone() && ! $isNext,
                ])>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <flux:icon :name="$step->icon()" class="mt-1 size-5 text-zinc-500 dark:text-zinc-400" />
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm">{{ $step->label() }}</flux:heading>
                                    <flux:badge :color="$status->badgeColor()" size="sm">{{ $status->badgeLabel() }}</flux:badge>
                                    @if ($step->isOptional())
                                        <flux:badge color="zinc" size="sm">{{ __('Optional') }}</flux:badge>
                                    @endif
                                </div>
                                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $step->description() }}
                                </flux:text>
                            </div>
                        </div>
                        <div class="flex flex-shrink-0 flex-col gap-2 sm:flex-row">
                            @if (! $status->isDone())
                                <flux:button size="sm" variant="primary" wire:click="focus('{{ $step->value }}')">
                                    {{ __('Zum Schritt') }}
                                </flux:button>
                                @if ($step->isOptional())
                                    <flux:button size="sm" variant="ghost" wire:click="skip('{{ $step->value }}')">
                                        {{ __('Überspringen') }}
                                    </flux:button>
                                @endif
                            @elseif ($status->manuallyCompleted)
                                <flux:button size="sm" variant="ghost" wire:click="focus('{{ $step->value }}')">
                                    {{ __('Ansehen') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
