<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

/**
 * Echter Stripe-API-Aufruf gegen den Test-Account, sobald `STRIPE_SECRET`
 * gesetzt ist. Ohne Key wird der Test übersprungen, damit CI ohne Secrets
 * grün bleiben kann.
 */
beforeEach(function () {
    if (blank(env('STRIPE_SECRET')) || blank(env('STRIPE_PRICE_STARTER_MONTHLY'))) {
        test()->markTestSkipped('STRIPE_SECRET / STRIPE_PRICE_STARTER_MONTHLY nicht gesetzt — Smoketest übersprungen.');
    }

    config([
        'features.billing' => true,
        'cashier.secret' => env('STRIPE_SECRET'),
        'cashier.key' => env('STRIPE_KEY'),
        'billing.plans.starter.monthly_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY'),
        'billing.addons.workshop.price_id' => env('STRIPE_PRICE_ADDON_WORKSHOP'),
    ]);
});

function billingActorWithTeam(): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::factory()->create(['name' => 'Smoke Test GmbH']);
    $team->memberships()->create([
        'user_id' => $user->id,
        'role' => TeamRole::Owner,
    ]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team->fresh()];
}

test('Plan-Auswahl baut eine echte Stripe-Checkout-Session und liefert eine checkout.stripe.com-URL', function () {
    [$user] = billingActorWithTeam();

    $component = Livewire::actingAs($user)
        ->test('pages::settings.billing')
        ->call('selectPlan', 'starter', 'monthly');

    $redirect = $component->effects['redirect'] ?? null;

    expect($redirect)->not->toBeNull()
        ->and($redirect)->toContain('checkout.stripe.com');
});

test('Add-on-Buchung (Workshop) baut eine Stripe-Checkout-Session im Mode payment', function () {
    [$user] = billingActorWithTeam();

    $component = Livewire::actingAs($user)
        ->test('pages::settings.billing')
        ->call('purchaseAddon', 'workshop');

    $redirect = $component->effects['redirect'] ?? null;

    expect($redirect)->not->toBeNull()
        ->and($redirect)->toContain('checkout.stripe.com');
});
