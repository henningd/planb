<?php

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating an insurance policy via the livewire page stores it scoped to company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::insurance-policies.index')
        ->set('type', InsuranceType::Cyber->value)
        ->set('insurer', 'Musterversicherung AG')
        ->set('policy_number', 'CY-2026-001')
        ->set('hotline', '0800 1122334')
        ->set('reporting_window', '24 Stunden')
        ->call('save')
        ->assertHasNoErrors();

    $policy = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();

    expect($policy)->not->toBeNull()
        ->and($policy->insurer)->toBe('Musterversicherung AG')
        ->and($policy->type)->toBe(InsuranceType::Cyber)
        ->and($policy->policy_number)->toBe('CY-2026-001')
        ->and($policy->reporting_window)->toBe('24 Stunden');
});

test('insurance policies are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber->value,
        'insurer' => 'Eigene AG',
    ]);

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'type' => InsuranceType::Cyber->value,
        'insurer' => 'Fremde AG',
    ]);

    $this->actingAs($user->fresh());

    expect(InsurancePolicy::pluck('insurer')->all())->toBe(['Eigene AG']);
});

test('validation requires insurer and valid type', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::insurance-policies.index')
        ->set('type', 'unsinn')
        ->set('insurer', '')
        ->call('save')
        ->assertHasErrors(['type', 'insurer']);
});

test('insurance page renders existing policies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber->value,
        'insurer' => 'Musterversicherung AG',
        'policy_number' => 'CY-2026-001',
        'hotline' => '0800 1122334',
        'reporting_window' => '24 Stunden',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('insurance-policies.index'))
        ->assertOk()
        ->assertSee('Versicherungen')
        ->assertSee('Musterversicherung AG')
        ->assertSee('Cyberversicherung')
        ->assertSee('CY-2026-001')
        ->assertSee('24 Stunden');
});

test('handbook print shows insurance section when policies exist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber->value,
        'insurer' => 'CyberSchutz24 AG',
        'policy_number' => 'CY-42',
        'reporting_window' => 'unverzüglich',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook.print'))
        ->assertOk()
        ->assertSee('Versicherungen')
        ->assertSee('CyberSchutz24 AG')
        ->assertSee('Cyberversicherung')
        ->assertSee('CY-42')
        ->assertSee('unverzüglich');
});
