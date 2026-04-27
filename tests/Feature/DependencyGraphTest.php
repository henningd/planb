<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\System;
use App\Models\User;
use App\Support\Graph\DependencyGraphBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('graph builder returns nodes and edges from system dependencies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    $a = System::factory()->for($company)->create(['name' => 'A', 'emergency_level_id' => $level->id]);
    $b = System::factory()->for($company)->create(['name' => 'B', 'emergency_level_id' => $level->id]);
    $c = System::factory()->for($company)->create(['name' => 'C', 'emergency_level_id' => $level->id]);
    $a->dependencies()->attach($b->id, ['sort' => 0]);
    $b->dependencies()->attach($c->id, ['sort' => 0]);

    $graph = DependencyGraphBuilder::build($company);

    expect($graph['stats']['systems'])->toBe(3);
    expect($graph['stats']['edges'])->toBe(2);
    expect($graph['stats']['cycles'])->toBe(0);
    expect($graph['stats']['isolated'])->toBe(0);
    expect($graph['nodes'])->toHaveCount(3);
    expect($graph['edges'])->toHaveCount(2);
    expect(collect($graph['nodes'])->pluck('data.label')->all())->toBe(['A', 'B', 'C']);
});

test('graph builder detects cycles', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $a = System::factory()->for($company)->create();
    $b = System::factory()->for($company)->create();
    $a->dependencies()->attach($b->id, ['sort' => 0]);
    $b->dependencies()->attach($a->id, ['sort' => 0]);

    $graph = DependencyGraphBuilder::build($company);

    expect($graph['stats']['cycles'])->toBe(2);
});

test('isolated systems are counted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::factory()->for($company)->count(3)->create();

    $graph = DependencyGraphBuilder::build($company);

    expect($graph['stats']['isolated'])->toBe(3);
    expect($graph['stats']['edges'])->toBe(0);
});

test('dependencies page renders with the graph data', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    System::factory()->for($company)->create(['name' => 'Server', 'emergency_level_id' => $level->id]);

    $this->actingAs($user->fresh())
        ->get(route('dependencies.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText('Abhängigkeiten')
        ->assertSee('dep-canvas', false);
});

test('dependencies page shows hint when no systems exist', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('dependencies.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText('Keine Systeme erfasst');
});
