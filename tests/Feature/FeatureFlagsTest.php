<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('compliance route is registered when feature flag is on', function () {
    config(['features.compliance' => true]);

    expect(Route::has('compliance.index'))->toBeTrue();
});

test('dependencies route is registered when feature flag is on', function () {
    config(['features.dependencies' => true]);

    expect(Route::has('dependencies.index'))->toBeTrue();
});

test('compliance link disappears from sidebar when flag is off', function () {
    config(['features.compliance' => false]);

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertDontSee(route('compliance.index', ['current_team' => $user->currentTeam->slug], false));
});

test('dependencies link disappears from sidebar when flag is off', function () {
    config(['features.dependencies' => false]);

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertDontSee('/dependencies');
});
