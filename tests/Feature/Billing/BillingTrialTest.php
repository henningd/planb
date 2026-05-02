<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    config(['features.billing' => true]);
});

test('Registrierung mit Plan=advanced startet 14-Tage-Trial auf dem neuen Team', function () {
    $this->withSession(['intended_plan' => 'advanced'])
        ->post(route('register.store'), [
            'name' => 'Trial Tester',
            'email' => 'trial@example.com',
            'password' => 'Sicheres-Passwort-1234!',
            'password_confirmation' => 'Sicheres-Passwort-1234!',
        ])->assertRedirect();

    $user = User::where('email', 'trial@example.com')->firstOrFail();
    $team = $user->currentTeam;

    expect($team)->not->toBeNull()
        ->and($team->trial_ends_at)->not->toBeNull()
        ->and($team->trial_ends_at->isFuture())->toBeTrue()
        ->and($team->trial_ends_at->isAfter(now()->addDays(13)))->toBeTrue();
});

test('Registrierung mit Plan=starter startet keinen Trial', function () {
    $this->withSession(['intended_plan' => 'starter'])
        ->post(route('register.store'), [
            'name' => 'Starter Person',
            'email' => 'starter@example.com',
            'password' => 'Sicheres-Passwort-1234!',
            'password_confirmation' => 'Sicheres-Passwort-1234!',
        ])->assertRedirect();

    $user = User::where('email', 'starter@example.com')->firstOrFail();

    expect($user->currentTeam->trial_ends_at)->toBeNull();
});

test('onGenericTrial wird korrekt erkannt während Trial-Zeitraum', function () {
    $team = Team::factory()->create([
        'trial_ends_at' => now()->addDays(7),
    ]);

    expect($team->onGenericTrial())->toBeTrue()
        ->and($team->isFrozen())->toBeFalse();
});

test('isFrozen wird true, sobald Trial abgelaufen und kein Abo gebucht ist', function () {
    $team = Team::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);

    expect($team->isFrozen())->toBeTrue()
        ->and($team->onGenericTrial())->toBeFalse();
});

test('isFrozen ist false, wenn Trial noch läuft', function () {
    $team = Team::factory()->create([
        'trial_ends_at' => now()->addDays(3),
    ]);

    expect($team->isFrozen())->toBeFalse();
});

test('isFrozen ist false, wenn freeze_after_trial deaktiviert ist', function () {
    config(['billing.freeze_after_trial' => false]);

    $team = Team::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);

    expect($team->isFrozen())->toBeFalse();
});
