<?php

use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\System;
use App\Models\User;
use App\Support\Graph\RecoveryTimelineBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('timeline orders systems by dependency: A → B → C', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $a = System::factory()->for($company)->create(['name' => 'A', 'rto_minutes' => 30]);
    $b = System::factory()->for($company)->create(['name' => 'B', 'rto_minutes' => 45]);
    $c = System::factory()->for($company)->create(['name' => 'C', 'rto_minutes' => 20]);
    $b->dependencies()->attach($a->id, ['sort' => 0]);
    $c->dependencies()->attach($a->id, ['sort' => 0]);
    $c->dependencies()->attach($b->id, ['sort' => 1]);

    $timeline = RecoveryTimelineBuilder::build($company);

    expect($timeline['entries'])->toHaveCount(3);
    expect(collect($timeline['entries'])->pluck('system.name')->all())->toBe(['A', 'B', 'C']);

    $byName = collect($timeline['entries'])->keyBy(fn ($e) => $e['system']->name);
    expect($byName['A']['start'])->toBe(0);
    expect($byName['A']['end'])->toBe(30);
    expect($byName['B']['start'])->toBe(30);
    expect($byName['B']['end'])->toBe(75);
    expect($byName['C']['start'])->toBe(75);
    expect($byName['C']['end'])->toBe(95);
    expect($timeline['total_minutes'])->toBe(95);
});

test('system without dependencies starts at minute 0', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::factory()->for($company)->create(['name' => 'Solo', 'rto_minutes' => 30]);

    $timeline = RecoveryTimelineBuilder::build($company);

    expect($timeline['entries'])->toHaveCount(1);
    expect($timeline['entries'][0]['start'])->toBe(0);
    expect($timeline['entries'][0]['end'])->toBe(30);
});

test('system with multiple dependencies starts at the latest end of all', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $fast = System::factory()->for($company)->create(['name' => 'Fast', 'rto_minutes' => 10]);
    $slow = System::factory()->for($company)->create(['name' => 'Slow', 'rto_minutes' => 90]);
    $app = System::factory()->for($company)->create(['name' => 'App', 'rto_minutes' => 15]);
    $app->dependencies()->attach($fast->id, ['sort' => 0]);
    $app->dependencies()->attach($slow->id, ['sort' => 1]);

    $timeline = RecoveryTimelineBuilder::build($company);

    $byName = collect($timeline['entries'])->keyBy(fn ($e) => $e['system']->name);
    expect($byName['App']['start'])->toBe(90);
    expect($byName['App']['end'])->toBe(105);
});

test('rto missing falls back to 60 minutes', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::factory()->for($company)->create(['name' => 'NoRto', 'rto_minutes' => null]);

    $timeline = RecoveryTimelineBuilder::build($company);

    expect($timeline['entries'])->toHaveCount(1);
    expect($timeline['entries'][0]['start'])->toBe(0);
    expect($timeline['entries'][0]['end'])->toBe(60);
    expect($timeline['entries'][0]['rto_missing'])->toBeTrue();
    expect($timeline['stats']['missing_rto'])->toBe(1);
});

test('cycle detection lists offending systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $a = System::factory()->for($company)->create(['name' => 'Alpha', 'rto_minutes' => 30]);
    $b = System::factory()->for($company)->create(['name' => 'Beta', 'rto_minutes' => 30]);
    $a->dependencies()->attach($b->id, ['sort' => 0]);
    $b->dependencies()->attach($a->id, ['sort' => 0]);

    $timeline = RecoveryTimelineBuilder::build($company);

    expect($timeline['cycles'])->toHaveCount(2);
    expect(collect($timeline['cycles'])->pluck('name')->all())->toBe(['Alpha', 'Beta']);
    expect($timeline['entries'])->toHaveCount(0);
    expect($timeline['stats']['cycles'])->toBe(2);
});

test('page renders the gantt bars', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $level = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    System::factory()->for($company)->create([
        'name' => 'Webshop',
        'rto_minutes' => 45,
        'emergency_level_id' => $level->id,
    ]);

    $this->actingAs($user->fresh())
        ->get(route('recovery-gantt.index', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()
        ->assertSeeText('Recovery-Zeitplan')
        ->assertSeeText('Webshop')
        ->assertSeeText('Wiederanlauf-Zeitleiste')
        ->assertSee('margin-left:', false)
        ->assertSee('width:', false);
});
