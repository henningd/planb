<?php

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('detail page shows all policy fields', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $policy = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber,
        'insurer' => 'Cyber-Pol AG',
        'policy_number' => 'CY-2026-007',
        'contact_name' => 'Anke Schaden',
        'hotline' => '0800 9988770',
        'email' => 'schaden@cyber-pol.example',
        'reporting_window' => '24 Stunden',
        'deductible' => '1.500 €',
        'notes' => 'Bei Ransomware bitte sofort Forensik-Hotline rufen.',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('insurance-policies.show', $policy))
        ->assertOk()
        ->assertSee('Cyber-Pol AG')
        ->assertSee('CY-2026-007')
        ->assertSee('Anke Schaden')
        ->assertSee('0800 9988770')
        ->assertSee('schaden@cyber-pol.example')
        ->assertSee('24 Stunden')
        ->assertSee('1.500 €')
        ->assertSee('Bei Ransomware');
});

test('delete from detail page removes the policy and redirects to the index', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $policy = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Liability,
        'insurer' => 'Wegwerf AG',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::insurance-policies.show', ['policy' => $policy])
        ->call('delete')
        ->assertRedirect(route('insurance-policies.index'));

    expect(InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->find($policy->id))->toBeNull();
});

test('insurance index dropdown links to the policy detail page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $policy = InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber,
        'insurer' => 'Index-Test-Versicherer',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('insurance-policies.index'))
        ->assertOk()
        ->assertSee(route('insurance-policies.show', $policy), false)
        ->assertSee('Details');
});
