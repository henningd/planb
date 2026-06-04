<?php

use App\Models\Company;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bootSystemForTemplate(): System
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();
    test()->actingAs($user->fresh());

    return System::create(['name' => 'Stromversorgung', 'category' => 'basisbetrieb']);
}

test('applyTaskTemplate creates the default tasks for a system without tasks', function () {
    $system = bootSystemForTemplate();

    Livewire\Livewire::test('pages::systems.edit', ['system' => $system])
        ->call('applyTaskTemplate');

    $titles = SystemTask::where('system_id', $system->id)->orderBy('sort')->pluck('title')->all();

    expect($titles)->toBe(['Prüfen', 'Sofortmaßnahme', 'Eskalation', 'Wiederherstellung', 'Kommunikation']);
});

test('applyTaskTemplate does nothing when tasks already exist', function () {
    $system = bootSystemForTemplate();
    SystemTask::create(['system_id' => $system->id, 'title' => 'Bestehende Aufgabe', 'sort' => 0]);

    Livewire\Livewire::test('pages::systems.edit', ['system' => $system])
        ->call('applyTaskTemplate');

    expect(SystemTask::where('system_id', $system->id)->count())->toBe(1);
});

test('the template button shows only when no tasks exist', function () {
    $system = bootSystemForTemplate();

    Livewire\Livewire::test('pages::systems.edit', ['system' => $system])
        ->assertSee('Vorlage übernehmen');

    SystemTask::create(['system_id' => $system->id, 'title' => 'Da', 'sort' => 0]);

    Livewire\Livewire::test('pages::systems.edit', ['system' => $system])
        ->assertDontSee('Vorlage übernehmen');
});
