<?php

namespace App\Support\Onboarding;

use App\Enums\OnboardingStep;

class OnboardingStepStatus
{
    public function __construct(
        public readonly OnboardingStep $step,
        public readonly bool $autoSatisfied,
        public readonly bool $manuallyCompleted,
        public readonly bool $manuallySkipped,
    ) {}

    public function isDone(): bool
    {
        return $this->autoSatisfied || $this->manuallyCompleted || $this->manuallySkipped;
    }

    public function badgeColor(): string
    {
        if ($this->autoSatisfied || $this->manuallyCompleted) {
            return 'emerald';
        }
        if ($this->manuallySkipped) {
            return 'zinc';
        }

        return 'amber';
    }

    public function badgeLabel(): string
    {
        if ($this->autoSatisfied) {
            return 'Erfüllt';
        }
        if ($this->manuallyCompleted) {
            return 'Abgehakt';
        }
        if ($this->manuallySkipped) {
            return 'Übersprungen';
        }

        return 'Offen';
    }
}
