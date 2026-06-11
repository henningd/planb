<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('roles pages return 404 when the feature is disabled', function () {
    config()->set('features.roles', false);

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $this->get(route('roles.index'))->assertNotFound();
    $this->get(route('roles.export'))->assertNotFound();
});

test('the sidebar hides the roles item when the feature is disabled', function () {
    config()->set('features.roles', false);

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Abteilungen / Rollen');
});

test('roles pages remain accessible when the feature is enabled', function () {
    config()->set('features.roles', true);

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('roles.index'))
        ->assertOk();
});
