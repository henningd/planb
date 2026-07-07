<?php

use App\Models\ApiToken;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a token can be created via the api-tokens page', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $c = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::api-tokens.index')
        ->call('openCreate')
        ->set('newName', 'Prometheus Test')
        ->call('createToken')
        ->assertHasNoErrors();

    expect((string) $c->get('issuedToken'))->toStartWith('planb_')
        ->and(ApiToken::withoutGlobalScopes()->count())->toBe(1);
});

test('without a company profile the page explains why creating is blocked', function () {
    $user = User::factory()->create(); // Team ohne Company (Einrichtung nicht abgeschlossen)

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::api-tokens.index')
        ->assertSee('Noch kein Firmenprofil')
        ->set('newName', 'Prometheus Test')
        ->call('createToken')
        ->assertHasNoErrors()
        ->assertSet('issuedToken', null);

    expect(ApiToken::withoutGlobalScopes()->count())->toBe(0);
});

test('a token name is required', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::api-tokens.index')
        ->set('newName', '')
        ->call('createToken')
        ->assertHasErrors(['newName' => 'required']);
});

test('the create modal actually renders the name input', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::api-tokens.index')
        ->assertSeeHtml('wire:model="newName"');
});
