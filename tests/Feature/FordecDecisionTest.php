<?php

use App\Models\Company;
use App\Models\CrisisLogEntry;
use App\Models\FordecDecision;
use App\Models\ScenarioRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

/**
 * @return array{0: User, 1: Company, 2: ScenarioRun}
 */
function fordecActiveRun(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $run = ScenarioRun::factory()->for($company)->create([
        'started_at' => now(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    return [$user->fresh(), $company, $run];
}

test('a FORDEC decision is saved and mirrored into the crisis log', function () {
    [$user, $company, $run] = fordecActiveRun();

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->set('fordecTitle', 'Verlagerung in den Ausweichstandort')
        ->set('fordecFacts', 'Serverraum ohne Kühlung, Temperatur steigt.')
        ->set('fordecOptions', 'Abwarten oder verlagern.')
        ->set('fordecRisksBenefits', 'Verlagerung kostet Zeit, schützt aber Hardware.')
        ->set('fordecDecision', 'Sofort in den Ausweichstandort verlagern.')
        ->set('fordecExecution', 'IT-Leitung koordiniert, bis 16:00 Uhr.')
        ->set('fordecCheckAt', '2026-07-10T16:30')
        ->call('saveFordec')
        ->assertHasNoErrors();

    $decision = FordecDecision::firstWhere('scenario_run_id', $run->id);
    expect($decision)->not->toBeNull()
        ->and($decision->company_id)->toBe($company->id)
        ->and($decision->title)->toBe('Verlagerung in den Ausweichstandort')
        ->and($decision->decision)->toBe('Sofort in den Ausweichstandort verlagern.')
        ->and($decision->check_at)->not->toBeNull()
        ->and($decision->created_by_name)->toBe($user->name);

    // Als Entscheidung ins Krisen-Logbuch gespiegelt.
    $logEntry = CrisisLogEntry::where('scenario_run_id', $run->id)->where('type', 'decision')->first();
    expect($logEntry)->not->toBeNull()
        ->and($logEntry->message)->toContain('FORDEC-Entscheidung')
        ->and($logEntry->message)->toContain('Sofort in den Ausweichstandort verlagern.');
});

test('the decision field is required', function () {
    [$user] = fordecActiveRun();

    Livewire::actingAs($user)
        ->test('pages::incident-mode.index')
        ->set('fordecFacts', 'Nur Fakten, keine Entscheidung.')
        ->set('fordecDecision', '')
        ->call('saveFordec')
        ->assertHasErrors(['fordecDecision']);

    expect(FordecDecision::count())->toBe(0);
});

test('no FORDEC decision is stored without an active run', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->set('fordecDecision', 'Entscheidung ohne Vorfall.')
        ->call('saveFordec')
        ->assertHasNoErrors();

    expect(FordecDecision::count())->toBe(0);
});
