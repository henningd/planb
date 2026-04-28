<?php

namespace App\Support\Onboarding;

use App\Enums\CrisisRole;
use App\Enums\OnboardingStep;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Employee;
use App\Models\HandbookVersion;
use App\Models\Location;
use App\Models\OnboardingState;
use App\Models\Role;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Scopes\CurrentCompanyScope;

class OnboardingService
{
    public static function progressFor(Company $company): OnboardingProgress
    {
        $state = self::ensureState($company);
        $manualCompleted = is_array($state->completed_steps) ? $state->completed_steps : [];
        $skipped = is_array($state->skipped_steps) ? $state->skipped_steps : [];

        $statuses = [];
        foreach (OnboardingStep::ordered() as $step) {
            $autoDone = self::stepAutoSatisfied($step, $company);
            $manuallyDone = in_array($step->value, $manualCompleted, true);
            $manuallySkipped = in_array($step->value, $skipped, true);

            $statuses[$step->value] = new OnboardingStepStatus(
                step: $step,
                autoSatisfied: $autoDone,
                manuallyCompleted: $manuallyDone,
                manuallySkipped: $manuallySkipped,
            );
        }

        return new OnboardingProgress(
            company: $company,
            state: $state,
            statuses: $statuses,
        );
    }

    public static function ensureState(Company $company): OnboardingState
    {
        $state = OnboardingState::query()
            ->withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->first();

        if ($state) {
            return $state;
        }

        return OnboardingState::query()
            ->forceCreate([
                'company_id' => $company->id,
                'current_step' => OnboardingStep::CompanyProfile->value,
            ]);
    }

    public static function markStepCompleted(Company $company, OnboardingStep $step): OnboardingProgress
    {
        $state = self::ensureState($company);
        $completed = is_array($state->completed_steps) ? $state->completed_steps : [];
        if (! in_array($step->value, $completed, true)) {
            $completed[] = $step->value;
        }
        $state->completed_steps = $completed;

        $skipped = is_array($state->skipped_steps) ? $state->skipped_steps : [];
        $skipped = array_values(array_filter($skipped, fn (string $s) => $s !== $step->value));
        $state->skipped_steps = $skipped;

        $state->save();

        $progress = self::progressFor($company);
        if ($progress->isFullyDone()) {
            $state->forceFill([
                'completed_at' => now(),
                'paused_at' => null,
                'dismissed_at' => null,
                'current_step' => null,
            ])->save();
        } else {
            $state->forceFill(['current_step' => $progress->nextStep()?->value])->save();
        }

        return self::progressFor($company);
    }

    public static function skipStep(Company $company, OnboardingStep $step): OnboardingProgress
    {
        if (! $step->isOptional()) {
            return self::progressFor($company);
        }

        $state = self::ensureState($company);
        $skipped = is_array($state->skipped_steps) ? $state->skipped_steps : [];
        if (! in_array($step->value, $skipped, true)) {
            $skipped[] = $step->value;
        }
        $state->skipped_steps = $skipped;
        $state->save();

        $progress = self::progressFor($company);
        $state->forceFill(['current_step' => $progress->nextStep()?->value])->save();

        return self::progressFor($company);
    }

    public static function pause(Company $company): void
    {
        $state = self::ensureState($company);
        $state->forceFill([
            'paused_at' => now(),
            'dismissed_at' => null,
        ])->save();
    }

    public static function resume(Company $company): void
    {
        $state = self::ensureState($company);
        $state->forceFill([
            'paused_at' => null,
            'dismissed_at' => null,
        ])->save();
    }

    public static function dismiss(Company $company): void
    {
        $state = self::ensureState($company);
        $state->forceFill([
            'dismissed_at' => now(),
        ])->save();
    }

    public static function restart(Company $company): void
    {
        $state = self::ensureState($company);
        $state->forceFill([
            'completed_steps' => [],
            'skipped_steps' => [],
            'paused_at' => null,
            'completed_at' => null,
            'dismissed_at' => null,
            'current_step' => OnboardingStep::CompanyProfile->value,
        ])->save();
    }

    private static function stepAutoSatisfied(OnboardingStep $step, Company $company): bool
    {
        return match ($step) {
            OnboardingStep::CompanyProfile => $company->name !== null
                && $company->name !== ''
                && $company->industry !== null,
            OnboardingStep::IndustryTemplate => false, // optional, nur manuell als done markierbar
            OnboardingStep::Locations => Location::query()
                ->where('company_id', $company->id)
                ->exists(),
            OnboardingStep::CrisisRoles => self::allCrisisRolesFilled($company),
            OnboardingStep::Employees => Employee::query()
                ->where('company_id', $company->id)
                ->count() >= 3,
            OnboardingStep::Systems => self::systemsClassified($company),
            OnboardingStep::ServiceProviders => ServiceProvider::query()
                ->where('company_id', $company->id)
                ->exists(),
            OnboardingStep::EmergencyResources => EmergencyResource::query()
                ->where('company_id', $company->id)
                ->exists(),
            OnboardingStep::HandbookRelease => HandbookVersion::query()
                ->where('company_id', $company->id)
                ->whereNotNull('approved_at')
                ->exists(),
        };
    }

    private static function allCrisisRolesFilled(Company $company): bool
    {
        foreach (CrisisRole::cases() as $crisis) {
            $role = Role::query()
                ->where('company_id', $company->id)
                ->where('system_key', $crisis->value)
                ->first();
            if (! $role) {
                return false;
            }
            $hasMain = $role->employees()
                ->wherePivot('is_deputy', false)
                ->exists();
            if (! $hasMain) {
                return false;
            }
        }

        return true;
    }

    private static function systemsClassified(Company $company): bool
    {
        $total = System::query()->where('company_id', $company->id)->count();
        if ($total < 3) {
            return false;
        }
        $unclassified = System::query()
            ->where('company_id', $company->id)
            ->whereNull('emergency_level_id')
            ->exists();

        return ! $unclassified;
    }
}
