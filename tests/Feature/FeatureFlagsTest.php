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

test('sidebar renders even when feature flag is on but the route is not registered', function () {
    // Simuliert Cache-Drift: features.compliance war beim route:cache aus,
    // ist beim Render aber an. Sidebar darf nicht mehr 500en.
    config(['features.compliance' => true]);

    $collection = app('router')->getRoutes();
    $property = new ReflectionProperty($collection, 'nameList');
    $property->setAccessible(true);
    $nameList = $property->getValue($collection);
    unset($nameList['compliance.index']);
    $property->setValue($collection, $nameList);

    expect(Route::has('compliance.index'))->toBeFalse();

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard', ['current_team' => $user->currentTeam->slug]))
        ->assertOk();
});
