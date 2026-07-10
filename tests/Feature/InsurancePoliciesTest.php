<?php

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\Role;
use App\Models\Scenario;
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

test('an insurance policy stores the extended fields and scenario links', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Versicherungs-Ansprechpartner',
    ]);
    $scenario = Scenario::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Cyberangriff',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::insurance-policies.index')
        ->set('type', InsuranceType::Cyber->value)
        ->set('insurer', 'CyberProtect AG')
        ->set('valid_until', '2026-12-31')
        ->set('coverage_amount', '5 Mio €')
        ->set('required_documents', 'Schadenanzeige, Forensik-Bericht')
        ->set('responsible_role_id', $role->id)
        ->set('approval_required', true)
        ->set('approval_note', 'Vor Forensik informieren.')
        ->set('claims_process_tested_at', '2026-06-01')
        ->set('next_review_at', '2026-01-01')
        ->set('selectedScenarios', [$scenario->id])
        ->call('save')
        ->assertHasNoErrors();

    $policy = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('insurer', 'CyberProtect AG')->firstOrFail();

    expect($policy->coverage_amount)->toBe('5 Mio €')
        ->and($policy->required_documents)->toBe('Schadenanzeige, Forensik-Bericht')
        ->and($policy->responsible_role_id)->toBe($role->id)
        ->and($policy->approval_required)->toBeTrue()
        ->and($policy->valid_until->toDateString())->toBe('2026-12-31')
        ->and($policy->isExpired())->toBeFalse()
        ->and($policy->isReviewOverdue())->toBeTrue()
        ->and($policy->scenarios()->count())->toBe(1);
});
