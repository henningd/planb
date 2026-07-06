<?php

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\User;
use App\Support\Onboarding\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the setup menu item is visible while onboarding is incomplete', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Einrichtung');
});

test('the setup menu item disappears once onboarding is fully completed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OnboardingService::ensureState($company)->forceFill(['completed_at' => now()])->save();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Einrichtung'));
});

test('the onboarding page stays directly reachable after completion', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    OnboardingService::ensureState($company)->forceFill(['completed_at' => now()])->save();

    $this->actingAs($user->fresh())
        ->get(route('onboarding.index'))
        ->assertOk();
});

test('the main navigation shows the emergency handbook entry for admins', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Notfallhandbuch')
        ->assertSee(route('handbook-versions.index'));
});

test('the handbook page shows the current version prominently above the history', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookVersion::factory()->for($company)->create([
        'version' => '2.3',
        'changed_at' => '2026-06-01',
        'approved_at' => '2026-06-02',
        'change_reason' => 'Jahresrevision',
    ]);
    HandbookVersion::factory()->for($company)->create([
        'version' => '1.0',
        'changed_at' => '2025-01-01',
        'approved_at' => '2025-01-02',
        'change_reason' => 'Erstausgabe',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('handbook-versions.index'))
        ->assertOk()
        ->assertSee('Notfallhandbuch')
        ->assertSee('Aktuelles Handbuch')
        ->assertSee('2.3')
        ->assertSee('Versionshistorie')
        ->assertSee('Erstausgabe');
});

test('the handbook page hints at a missing approved version', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook-versions.index'))
        ->assertOk()
        ->assertSee('Noch keine freigegebene Handbuch-Version');
});

test('the previous version history url keeps working', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get('/'.$user->currentTeam->slug.'/handbook-versions')
        ->assertOk();
});

test('the sidebar shows the notification bell and the icon-only help button', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="notification-bell-trigger"', false)
        ->assertSee('data-test="sidebar-help-button"', false)
        ->assertSee('Hilfe & Handbuch');
});
