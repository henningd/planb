<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\EmergencyLevel;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('contacts index renders and lists only own contacts', function () {
    $user = adminPagesUser();

    Contact::factory()->for($user->currentCompany())->create(['name' => 'Erika Mustermann']);

    // Another tenant's contact must not leak into the current user's view.
    $otherCompany = Company::factory()->for(Team::factory())->create();
    Contact::factory()->for($otherCompany)->create(['name' => 'Stranger']);

    $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('Erika Mustermann')
        ->assertDontSee('Stranger');
});

test('emergency levels index renders', function () {
    $user = adminPagesUser();

    EmergencyLevel::factory()->for($user->currentCompany())->create(['name' => 'Kritisch']);

    $this->actingAs($user)
        ->get(route('emergency-levels.index'))
        ->assertOk()
        ->assertSee('Kritisch');
});
