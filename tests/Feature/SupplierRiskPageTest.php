<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\SupplierCriticality;
use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\SupplierRiskAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function actingUserWithProvider(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $provider = ServiceProvider::factory()->create([
        'company_id' => $company->id,
    ]);

    return [$user->fresh(), $provider];
}

test('the supplier risk page lists assessments of the current company', function () {
    [$user, $provider] = actingUserWithProvider();

    SupplierRiskAssessment::factory()->forProvider($provider)->create([
        'criticality' => SupplierCriticality::Kritisch,
    ]);

    $this->actingAs($user)
        ->get(route('supplier-risk.index'))
        ->assertOk()
        ->assertSee($provider->name);
});

test('an assessment can be created through the Livewire component', function () {
    [$user, $provider] = actingUserWithProvider();

    Livewire::actingAs($user)
        ->test('pages::supplier-risk.index')
        ->set('service_provider_id', $provider->id)
        ->set('criticality', SupplierCriticality::Hoch->value)
        ->set('interval', PreventiveMeasureInterval::Yearly->value)
        ->call('save')
        ->assertHasNoErrors();

    $assessment = SupplierRiskAssessment::firstWhere('service_provider_id', $provider->id);

    expect($assessment)->not->toBeNull()
        ->and($assessment->company_id)->toBe($provider->company_id)
        ->and($assessment->criticality)->toBe(SupplierCriticality::Hoch)
        ->and($assessment->next_assessment_at)->not->toBeNull(); // aus Intervall abgeleitet
});

test('marking an assessment advances the next assessment date', function () {
    [$user, $provider] = actingUserWithProvider();

    $assessment = SupplierRiskAssessment::factory()->forProvider($provider)->overdue()->create();

    Livewire::actingAs($user)
        ->test('pages::supplier-risk.index')
        ->call('markAssessed', $assessment->id);

    $assessment->refresh();

    expect($assessment->last_assessed_at)->not->toBeNull()
        ->and($assessment->next_assessment_at->isFuture())->toBeTrue();
});
