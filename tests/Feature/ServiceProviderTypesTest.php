<?php

use App\Enums\ServiceProviderType;
use App\Models\Company;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can save type and direct order limit on a service provider', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::service-providers.index')
        ->set('name', 'BSI Meldestelle')
        ->set('type', ServiceProviderType::BsiReportingOffice->value)
        ->set('hotline', '0228 999582-0')
        ->set('direct_order_limit', '500')
        ->call('save')
        ->assertHasNoErrors();

    $provider = ServiceProvider::where('name', 'BSI Meldestelle')->firstOrFail();
    expect($provider->type)->toBe(ServiceProviderType::BsiReportingOffice)
        ->and((float) $provider->direct_order_limit)->toBe(500.0);
});

test('filter narrows the list by type', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ServiceProvider::factory()->for($company)->ofType(ServiceProviderType::ItMsp)->create(['name' => 'IT Vendor']);
    ServiceProvider::factory()->for($company)->ofType(ServiceProviderType::Utility)->create(['name' => 'Stadtwerke']);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::service-providers.index')
        ->set('filterType', ServiceProviderType::Utility->value)
        ->assertSee('Stadtwerke')
        ->assertDontSee('IT Vendor');
});

test('invalid service provider type is rejected', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::service-providers.index')
        ->set('name', 'Foo')
        ->set('type', 'unsinn')
        ->call('save')
        ->assertHasErrors(['type']);
});
