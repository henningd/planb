<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function adminPagesUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

test('company edit page renders', function () {
    $user = adminPagesUser();

    $this->actingAs($user)
        ->get(route('company.edit'))
        ->assertOk()
        ->assertSee('Firmenprofil');
});

test('emergency levels index renders', function () {
    $user = adminPagesUser();

    EmergencyLevel::factory()->for($user->currentCompany())->create(['name' => 'Kritisch']);

    $this->actingAs($user)
        ->get(route('emergency-levels.index'))
        ->assertOk()
        ->assertSee('Kritisch');
});

test('emergency level cannot be created with an already-used sort number', function () {
    $user = adminPagesUser();

    EmergencyLevel::factory()->for($user->currentCompany())->create(['name' => 'Hoch', 'sort' => 7]);

    $this->actingAs($user);

    Livewire::test('pages::emergency-levels.index')
        ->set('name', 'Eigene Stufe')
        ->set('sort', 7)
        ->call('save')
        ->assertHasErrors(['sort' => 'unique']);

    expect(EmergencyLevel::where('name', 'Eigene Stufe')->exists())->toBeFalse();
});

test('emergency level cannot be updated to an already-used sort number', function () {
    $user = adminPagesUser();
    $company = $user->currentCompany();

    EmergencyLevel::factory()->for($company)->create(['name' => 'Stufe A', 'sort' => 7]);
    $second = EmergencyLevel::factory()->for($company)->create(['name' => 'Stufe B', 'sort' => 8]);

    $this->actingAs($user);

    Livewire::test('pages::emergency-levels.index')
        ->call('openEdit', $second->id)
        ->set('sort', 7)
        ->call('save')
        ->assertHasErrors(['sort' => 'unique']);

    expect($second->refresh()->sort)->toBe(8);
});

test('emergency level can be saved with its own existing sort number when editing', function () {
    $user = adminPagesUser();
    $level = EmergencyLevel::factory()->for($user->currentCompany())->create(['name' => 'Stufe A', 'sort' => 7]);

    $this->actingAs($user);

    Livewire::test('pages::emergency-levels.index')
        ->call('openEdit', $level->id)
        ->set('name', 'Stufe A (neu)')
        ->call('save')
        ->assertHasNoErrors();

    expect($level->refresh()->name)->toBe('Stufe A (neu)');
});

test('emergency level sort numbers are scoped per company', function () {
    $userA = adminPagesUser();
    $userB = adminPagesUser();

    EmergencyLevel::factory()->for($userB->currentCompany())->create(['sort' => 7]);

    $this->actingAs($userA);

    Livewire::test('pages::emergency-levels.index')
        ->set('name', 'Stufe A')
        ->set('sort', 7)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        EmergencyLevel::where('company_id', $userA->currentCompany()->id)
            ->where('sort', 7)
            ->exists()
    )->toBeTrue();
});
