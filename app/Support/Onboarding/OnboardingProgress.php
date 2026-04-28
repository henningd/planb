<?php

namespace App\Support\Onboarding;

use App\Enums\OnboardingStep;
use App\Models\Company;
use App\Models\OnboardingState;

class OnboardingProgress
{
    /**
     * @param  array<string, OnboardingStepStatus>  $statuses  keyed by step value
     */
    public function __construct(
        public readonly Company $company,
        public readonly OnboardingState $state,
        public readonly array $statuses,
    ) {}

    /**
     * @return list<OnboardingStepStatus>
     */
    public function ordered(): array
    {
        $list = [];
        foreach (OnboardingStep::ordered() as $step) {
            $list[] = $this->statuses[$step->value];
        }

        return $list;
    }

    public function statusFor(OnboardingStep $step): OnboardingStepStatus
    {
        return $this->statuses[$step->value];
    }

    public function totalSteps(): int
    {
        return count($this->statuses);
    }

    public function doneSteps(): int
    {
        return collect($this->statuses)->filter(fn (OnboardingStepStatus $s) => $s->isDone())->count();
    }

    public function percentage(): int
    {
        if ($this->totalSteps() === 0) {
            return 0;
        }

        return (int) round($this->doneSteps() / $this->totalSteps() * 100);
    }

    public function nextStep(): ?OnboardingStep
    {
        foreach (OnboardingStep::ordered() as $step) {
            if (! $this->statuses[$step->value]->isDone()) {
                return $step;
            }
        }

        return null;
    }

    public function isFullyDone(): bool
    {
        return $this->nextStep() === null;
    }

    public function shouldShowOnDashboard(): bool
    {
        if ($this->state->isCompleted() || $this->isFullyDone()) {
            return false;
        }
        if ($this->state->isDismissed()) {
            return false;
        }

        return true;
    }
}
