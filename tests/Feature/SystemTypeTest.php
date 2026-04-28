<?php

use App\Enums\SystemType;
use App\Models\Company;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('saves a system_type when the dropdown is set', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Active Directory',
        'category' => 'basisbetrieb',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('system_type', SystemType::Server->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->system_type)->toBe(SystemType::Server);
});

it('clears system_type when the empty option is chosen', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Telefonie',
        'category' => 'basisbetrieb',
        'system_type' => SystemType::Kommunikation->value,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('system_type', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($system->fresh()->system_type)->toBeNull();
});

it('rejects an unknown system_type value', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'X',
        'category' => 'basisbetrieb',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->set('system_type', 'nonsense')
        ->call('save')
        ->assertHasErrors(['system_type']);
});

it('preselects the existing system_type when opening the form', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Mailserver',
        'category' => 'basisbetrieb',
        'system_type' => SystemType::Anwendung->value,
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::systems.edit', ['system' => $system])
        ->assertSet('system_type', SystemType::Anwendung->value);
});

it('exposes all four required system types', function () {
    expect(array_map(fn ($c) => $c->value, SystemType::cases()))->toBe([
        'anwendung', 'kommunikation', 'server', 'infrastruktur',
    ]);

    expect(array_map(fn ($c) => $c->label(), SystemType::cases()))->toBe([
        'Anwendung', 'Kommunikation', 'Server', 'Infrastruktur',
    ]);
});
