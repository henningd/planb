<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\CarbonInterface;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the company casts the portal link timestamps to Carbon', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'portal_link_generated_at' => now()->subDay(),
        'portal_link_last_used_at' => now(),
    ]);

    $fresh = $company->fresh();

    expect($fresh->portal_link_generated_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($fresh->portal_link_last_used_at)->toBeInstanceOf(CarbonInterface::class);
});

test('system settings renders the portal token dates without an isoFormat error', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'portal_api_token_hash' => hash('sha256', 'token'),
        'portal_link_generated_at' => now()->subDay(),
        'portal_link_last_used_at' => now(),
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::system-settings.index')
        ->assertOk()
        ->assertSee('Erzeugt am');
});
