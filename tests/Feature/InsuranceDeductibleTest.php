<?php

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('insurance policy stores and renders deductible', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::insurance-policies.index')
        ->set('type', InsuranceType::Cyber->value)
        ->set('insurer', 'Cyber AG')
        ->set('deductible', '1.500 €')
        ->call('save')
        ->assertHasNoErrors();

    expect(InsurancePolicy::where('insurer', 'Cyber AG')->first()?->deductible)->toBe('1.500 €');

    $this->actingAs($user->fresh())
        ->get(route('insurance-policies.index'))
        ->assertOk()
        ->assertSee('1.500 €');
});
