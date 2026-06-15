<?php

use App\Enums\PreventiveMeasureStatus;
use App\Enums\SystemType;
use App\Models\Company;
use App\Models\PreventiveMeasure;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function systemForUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create([
        'company_id' => $company->id,
        'system_type' => SystemType::Server,
    ]);

    return [$user->fresh(), $system];
}

test('the system page shows the prevention tab with its measures', function () {
    [$user, $system] = systemForUser();

    PreventiveMeasure::factory()->forSystem($system)->create(['title' => 'USV-Wartung & Test']);

    $this->actingAs($user)
        ->get(route('systems.show', $system))
        ->assertOk()
        ->assertSee('Prävention')
        ->assertSee('USV-Wartung & Test');
});

test('importing the catalog from the system page seeds suggested measures', function () {
    [$user, $system] = systemForUser();

    Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('importMeasureCatalog');

    expect($system->preventiveMeasures()->count())->toBeGreaterThan(3);
});

test('the prevention tab is hidden when the feature is disabled', function () {
    [$user, $system] = systemForUser();

    PreventiveMeasure::factory()->forSystem($system)->create(['title' => 'Versteckte Maßnahme']);

    config(['features.preventive_measures' => false]);

    $this->actingAs($user)
        ->get(route('systems.show', $system))
        ->assertOk()
        ->assertDontSee('Prävention')
        ->assertDontSee('Versteckte Maßnahme');
});

test('marking a measure executed from the system page advances its due date', function () {
    [$user, $system] = systemForUser();

    $measure = PreventiveMeasure::factory()->forSystem($system)->overdue()->create([
        'status' => PreventiveMeasureStatus::Planned,
    ]);

    Livewire::actingAs($user)
        ->test('pages::systems.show', ['system' => $system])
        ->call('markMeasureExecuted', $measure->id);

    $measure->refresh();

    expect($measure->status)->toBe(PreventiveMeasureStatus::Active)
        ->and($measure->next_due_at->isFuture())->toBeTrue();
});
