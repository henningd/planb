<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sidebar groups default to collapsed for a fresh user', function () {
    $user = User::factory()->create();

    expect($user->isSidebarGroupExpanded('handbook'))->toBeFalse();
    expect($user->isSidebarGroupExpanded('emergency'))->toBeFalse();
    expect($user->isSidebarGroupExpanded('team'))->toBeFalse();
});

test('preference setter persists state per group key', function () {
    $user = User::factory()->create();

    $user->setSidebarGroupExpanded('handbook', true);
    $user->setSidebarGroupExpanded('emergency', false);

    $user->refresh();

    expect($user->isSidebarGroupExpanded('handbook'))->toBeTrue();
    expect($user->isSidebarGroupExpanded('emergency'))->toBeFalse();
    expect($user->isSidebarGroupExpanded('team'))->toBeFalse();
});

test('endpoint stores the toggled state for the authenticated user', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user)
        ->patchJson(route('preferences.sidebar-group'), [
            'key' => 'handbook',
            'expanded' => true,
        ])
        ->assertNoContent();

    expect($user->fresh()->isSidebarGroupExpanded('handbook'))->toBeTrue();
});

test('endpoint accepts the administration group key for super admins', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->patchJson(route('preferences.sidebar-group'), [
            'key' => 'administration',
            'expanded' => true,
        ])
        ->assertNoContent();

    expect($admin->fresh()->isSidebarGroupExpanded('administration'))->toBeTrue();
});

test('endpoint accepts the settings group key for super admins', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->patchJson(route('preferences.sidebar-group'), [
            'key' => 'settings',
            'expanded' => true,
        ])
        ->assertNoContent();

    expect($admin->fresh()->isSidebarGroupExpanded('settings'))->toBeTrue();
});

test('super admin sees the new system settings menu item', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    Company::factory()->for($admin->currentTeam)->create();

    $this->actingAs($admin->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-sidebar-key="settings"', false)
        ->assertSee(route('admin.settings.system.index'), false);
});

test('non super admin cannot reach system settings page', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user->fresh())
        ->get(route('admin.settings.system.index'))
        ->assertForbidden();
});

test('administration sidebar group renders expandable with persisted state for super admins', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    Company::factory()->for($admin->currentTeam)->create();
    $admin->setSidebarGroupExpanded('administration', true);

    $this->actingAs($admin->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-sidebar-key="administration"', false);
});

test('endpoint rejects unknown group keys', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson(route('preferences.sidebar-group'), [
            'key' => 'evil',
            'expanded' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('key');
});

test('endpoint requires authentication', function () {
    $this->patchJson(route('preferences.sidebar-group'), [
        'key' => 'handbook',
        'expanded' => true,
    ])->assertUnauthorized();
});

test('sidebar renders expandable groups with the persisted state', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();
    $user->setSidebarGroupExpanded('handbook', true);

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-sidebar-key="handbook"', false)
        ->assertSee('data-sidebar-key="emergency"', false)
        ->assertSee('data-sidebar-key="team"', false);
});
