<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the communication templates page links to its manual chapter', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->assertSee(route('manual.show', 'kommunikations-vorlagen'), false);
});

test('the crisis cockpit links to its manual chapter', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::incident-mode.index')
        ->assertSee(route('manual.show', 'krisen-cockpit'), false);
});
