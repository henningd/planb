<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
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

test('emergency levels index renders', function () {
    $user = adminPagesUser();

    EmergencyLevel::factory()->for($user->currentCompany())->create(['name' => 'Kritisch']);

    $this->actingAs($user)
        ->get(route('emergency-levels.index'))
        ->assertOk()
        ->assertSee('Kritisch');
});
