<?php

use App\Enums\OnboardingStep;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OnboardingState;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Onboarding\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts an onboarding state on first read for a company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $progress = OnboardingService::progressFor($company);

    expect($progress->state->current_step)->toBe(OnboardingStep::CompanyProfile->value);
    expect($progress->totalSteps())->toBe(count(OnboardingStep::ordered()));
    expect($progress->isFullyDone())->toBeFalse();
});

it('auto-detects completed steps based on actual data', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Location::factory()->for($company)->create(['is_headquarters' => true]);
    foreach (range(1, 4) as $i) {
        Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
            'company_id' => $company->id,
            'first_name' => 'E'.$i,
            'last_name' => 'L',
        ]);
    }

    $progress = OnboardingService::progressFor($company);

    expect($progress->statusFor(OnboardingStep::Locations)->isDone())->toBeTrue();
    expect($progress->statusFor(OnboardingStep::Employees)->isDone())->toBeTrue();
    expect($progress->statusFor(OnboardingStep::Systems)->isDone())->toBeFalse();
});

it('allows skipping the optional industry-template step', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OnboardingService::skipStep($company, OnboardingStep::IndustryTemplate);

    $progress = OnboardingService::progressFor($company);
    expect($progress->statusFor(OnboardingStep::IndustryTemplate)->isDone())->toBeTrue();
    expect($progress->statusFor(OnboardingStep::IndustryTemplate)->manuallySkipped)->toBeTrue();
});

it('refuses to skip a non-optional step', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OnboardingService::skipStep($company, OnboardingStep::Systems);

    $progress = OnboardingService::progressFor($company);
    expect($progress->statusFor(OnboardingStep::Systems)->manuallySkipped)->toBeFalse();
});

it('marks the wizard fully complete once all steps are satisfied', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    foreach (OnboardingStep::ordered() as $step) {
        if ($step->isOptional()) {
            OnboardingService::skipStep($company, $step);
        } else {
            OnboardingService::markStepCompleted($company, $step);
        }
    }

    $progress = OnboardingService::progressFor($company);
    expect($progress->isFullyDone())->toBeTrue();
    expect($progress->state->fresh()->completed_at)->not->toBeNull();
    expect($progress->percentage())->toBe(100);
});

it('pauses and resumes the wizard', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OnboardingService::pause($company);
    $state = OnboardingService::progressFor($company)->state->fresh();
    expect($state->isPaused())->toBeTrue();

    OnboardingService::resume($company);
    $state = OnboardingService::progressFor($company)->state->fresh();
    expect($state->isPaused())->toBeFalse();
});

it('hides the dashboard widget when dismissed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $before = OnboardingService::progressFor($company);
    expect($before->shouldShowOnDashboard())->toBeTrue();

    OnboardingService::dismiss($company);
    $after = OnboardingService::progressFor($company);
    expect($after->shouldShowOnDashboard())->toBeFalse();
    expect($after->state->fresh()->isDismissed())->toBeTrue();
});

it('hides the dashboard widget when fully completed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    foreach (OnboardingStep::ordered() as $step) {
        if ($step->isOptional()) {
            OnboardingService::skipStep($company, $step);
        } else {
            OnboardingService::markStepCompleted($company, $step);
        }
    }

    expect(OnboardingService::progressFor($company)->shouldShowOnDashboard())->toBeFalse();
});

it('renders the wizard page with the correct progress', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Location::factory()->for($company)->create();

    $this->actingAs($user->fresh())
        ->get(route('onboarding.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText(OnboardingStep::CompanyProfile->label())
        ->assertSeeText(OnboardingStep::Locations->label());
});

it('restarts the wizard cleanly', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    OnboardingService::markStepCompleted($company, OnboardingStep::CompanyProfile);

    OnboardingService::restart($company);

    $state = OnboardingState::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();
    expect($state->completed_steps)->toBe([]);
    expect($state->completed_at)->toBeNull();
    expect($state->current_step)->toBe(OnboardingStep::CompanyProfile->value);
});

it('keeps onboarding state per company on tenant isolation', function () {
    $user1 = User::factory()->create();
    $company1 = Company::factory()->for($user1->currentTeam)->create();
    $user2 = User::factory()->create();
    $company2 = Company::factory()->for($user2->currentTeam)->create();

    OnboardingService::markStepCompleted($company1, OnboardingStep::CompanyProfile);

    $progress2 = OnboardingService::progressFor($company2);
    expect($progress2->statusFor(OnboardingStep::CompanyProfile)->manuallyCompleted)->toBeFalse();
});
