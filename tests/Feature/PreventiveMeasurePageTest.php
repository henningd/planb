<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use App\Enums\SystemType;
use App\Models\Company;
use App\Models\PreventiveMeasure;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function actingUserWithSystem(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create([
        'company_id' => $company->id,
        'system_type' => SystemType::Server,
    ]);

    return [$user->fresh(), $system];
}

test('the prevention page lists measures of the current company', function () {
    [$user, $system] = actingUserWithSystem();

    PreventiveMeasure::factory()->forSystem($system)->create(['title' => 'Backup-Rückspieltest']);

    $this->actingAs($user)
        ->get(route('preventive-measures.index'))
        ->assertOk()
        ->assertSee('Backup-Rückspieltest');
});

test('a measure can be created through the Livewire component', function () {
    [$user, $system] = actingUserWithSystem();

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->set('system_id', $system->id)
        ->set('title', 'Patch-Management')
        ->set('interval', PreventiveMeasureInterval::Monthly->value)
        ->call('save')
        ->assertHasNoErrors();

    $measure = PreventiveMeasure::firstWhere('title', 'Patch-Management');

    expect($measure)->not->toBeNull()
        ->and($measure->system_id)->toBe($system->id)
        ->and($measure->company_id)->toBe($system->company_id)
        ->and($measure->next_due_at)->not->toBeNull(); // aus Intervall abgeleitet
});

test('marking a recurring measure as executed advances the next due date', function () {
    [$user, $system] = actingUserWithSystem();

    $measure = PreventiveMeasure::factory()->forSystem($system)->overdue()->create([
        'status' => PreventiveMeasureStatus::Planned,
    ]);

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->call('markExecuted', $measure->id);

    $measure->refresh();

    expect($measure->status)->toBe(PreventiveMeasureStatus::Active)
        ->and($measure->next_due_at->isFuture())->toBeTrue();
});

test('the catalog import creates suggested measures for the selected system', function () {
    [$user, $system] = actingUserWithSystem();

    Livewire::actingAs($user)
        ->test('pages::preventive-measures.index')
        ->set('filterSystem', $system->id)
        ->call('importCatalog');

    // Server-Typ: gemeinsame + serverspezifische Vorschläge.
    expect($system->preventiveMeasures()->count())->toBeGreaterThan(3)
        ->and($system->preventiveMeasures()->where('title', 'USV-Wartung & Test')->exists())->toBeTrue();
});

test('the catalog import is idempotent and does not duplicate measures', function () {
    [$user, $system] = actingUserWithSystem();

    $component = Livewire::actingAs($user)->test('pages::preventive-measures.index')->set('filterSystem', $system->id);
    $component->call('importCatalog');
    $first = $system->preventiveMeasures()->count();
    $component->call('importCatalog');

    expect($system->preventiveMeasures()->count())->toBe($first);
});
