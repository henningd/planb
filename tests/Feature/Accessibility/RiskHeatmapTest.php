<?php

use App\Models\Company;
use App\Models\Risk;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the numeric score in every heatmap cell', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.index');

    // Hoch-/Eckwerte des 5×5-Rasters müssen als Text erscheinen.
    foreach ([1, 4, 5, 9, 10, 12, 15, 16, 20, 25] as $score) {
        $component->assertSeeText('Score '.$score);
    }
});

it('renders a severity icon for every level in the heatmap', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.index');

    // Die Heatmap zeigt für jede Severity-Stufe das passende Heroicon
    // mindestens einmal — über alle 25 Zellen verteilt.
    $component->assertSee('data-severity-icon="shield-exclamation"', false)
        ->assertSee('data-severity-icon="exclamation-triangle"', false)
        ->assertSee('data-severity-icon="eye"', false)
        ->assertSee('data-severity-icon="check"', false);
});

it('shows a legend with all four severity buckets', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.index');

    $component->assertSeeText('Legende')
        ->assertSeeText('Niedrig')
        ->assertSeeText('Mittel')
        ->assertSeeText('Hoch')
        ->assertSeeText('Kritisch')
        ->assertSeeText('Score 1–4')
        ->assertSeeText('Score 5–9')
        ->assertSeeText('Score 10–14')
        ->assertSeeText('Score ≥ 15');
});

it('renders the count of risks per cell as explicit text', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // Drei Risiken in Zelle (W=5, S=5) → Score 25, kritisch.
    foreach (range(1, 3) as $index) {
        Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
            'company_id' => $company->id,
            'title' => 'Kritisch '.$index,
            'probability' => 5,
            'impact' => 5,
        ]);
    }

    // Ein Risiko in Zelle (W=1, S=1) → Score 1, niedrig.
    Risk::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'title' => 'Niedrig 1',
        'probability' => 1,
        'impact' => 1,
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::risks.index');

    $component->assertSeeText('3 Risiken')
        ->assertSeeText('1 Risiko');
});

it('exposes severity icons via the model helper', function () {
    expect(Risk::iconForScore(25))->toBe('shield-exclamation')
        ->and(Risk::iconForScore(15))->toBe('shield-exclamation')
        ->and(Risk::iconForScore(12))->toBe('exclamation-triangle')
        ->and(Risk::iconForScore(10))->toBe('exclamation-triangle')
        ->and(Risk::iconForScore(9))->toBe('eye')
        ->and(Risk::iconForScore(5))->toBe('eye')
        ->and(Risk::iconForScore(4))->toBe('check')
        ->and(Risk::iconForScore(1))->toBe('check');
});
