<?php

use App\Models\Team;

beforeEach(function () {
    config([
        'features.billing' => true,
        'billing.plans.starter.monthly_price_id' => 'price_starter_m',
        'billing.plans.starter.yearly_price_id' => 'price_starter_y',
        'billing.plans.advanced.monthly_price_id' => 'price_advanced_m',
        'billing.plans.advanced.yearly_price_id' => 'price_advanced_y',
    ]);
});

test('activePlanKey liefert den passenden Plan-Schlüssel zur Stripe-Price-ID', function () {
    $team = Team::factory()->create();
    $team->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.bin2hex(random_bytes(8)),
        'stripe_status' => 'active',
        'stripe_price' => 'price_advanced_y',
        'quantity' => 1,
    ]);

    expect($team->activePlanKey())->toBe('advanced');
});

test('activePlanKey liefert den Trial-Plan, solange Generic-Trial aktiv ist', function () {
    $team = Team::factory()->create([
        'trial_ends_at' => now()->addDays(5),
    ]);

    expect($team->activePlanKey())->toBe(config('billing.trial_plan'));
});

test('activePlanKey liefert null ohne Abo und ohne Trial', function () {
    $team = Team::factory()->create();

    expect($team->activePlanKey())->toBeNull();
});

test('onPlan vergleicht Tier-Stufen korrekt', function () {
    $team = Team::factory()->create();
    $team->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.bin2hex(random_bytes(8)),
        'stripe_status' => 'active',
        'stripe_price' => 'price_advanced_m',
        'quantity' => 1,
    ]);

    expect($team->onPlan('starter'))->toBeTrue()
        ->and($team->onPlan('advanced'))->toBeTrue()
        ->and($team->onPlan('enterprise'))->toBeFalse();
});

test('onPlan liefert false ohne aktiven Plan', function () {
    $team = Team::factory()->create();

    expect($team->onPlan('starter'))->toBeFalse();
});
