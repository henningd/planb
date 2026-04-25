<?php

use App\Enums\EmergencyResourceType;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create an emergency resource', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resources.index')
        ->set('type', EmergencyResourceType::EmergencyCash->value)
        ->set('name', 'Hauptkasse')
        ->set('location', 'Empfang')
        ->set('access_holders', 'GF, Empfang')
        ->call('save')
        ->assertHasNoErrors();

    expect(EmergencyResource::count())->toBe(1);
});

test('overdue check is flagged', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $overdue = EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->subDay()->toDateString(),
    ]);
    $fresh = EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDay()->toDateString(),
    ]);

    expect($overdue->fresh()->isOverdue())->toBeTrue()
        ->and($fresh->fresh()->isOverdue())->toBeFalse();
});

test('resources are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    EmergencyResource::factory()->for($company)->create(['name' => 'eigen']);
    EmergencyResource::factory()->for($other)->create(['name' => 'fremd']);

    $this->actingAs($user->fresh());

    expect(EmergencyResource::pluck('name')->all())->toBe(['eigen']);
    expect(EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

test('user can delete a resource', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $resource = EmergencyResource::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::emergency-resources.index')
        ->call('confirmDelete', $resource->id)
        ->call('delete');

    expect(EmergencyResource::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});
