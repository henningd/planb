<?php

use App\Models\Company;
use App\Models\FallbackProcess;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create a fallback process', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::fallback-processes.index')
        ->set('title', 'Papierbasierter Auftragsdurchlauf')
        ->set('description', 'Aufträge auf Lieferschein, später nachbuchen.')
        ->set('trigger', 'ERP länger als 2 Stunden nicht erreichbar.')
        ->set('priority', 1)
        ->set('max_duration_hours', 48)
        ->call('save')
        ->assertHasNoErrors();

    expect(FallbackProcess::count())->toBe(1);
    $process = FallbackProcess::first();
    expect($process->title)->toBe('Papierbasierter Auftragsdurchlauf');
    expect($process->priority)->toBe(1);
    expect($process->max_duration_hours)->toBe(48);
});

test('fallback process can link multiple systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $erp = System::factory()->for($company)->create(['name' => 'ERP']);
    $wms = System::factory()->for($company)->create(['name' => 'Lagerverwaltung']);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::fallback-processes.index')
        ->set('title', 'Papier-Lieferschein')
        ->set('system_ids', [$erp->id, $wms->id])
        ->call('save')
        ->assertHasNoErrors();

    $process = FallbackProcess::first();
    expect($process->systems)->toHaveCount(2);
    expect($process->systems->pluck('id')->all())->toEqualCanonicalizing([$erp->id, $wms->id]);
});

test('fallback process can be saved without any system link', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::fallback-processes.index')
        ->set('title', 'Telefonkette statt E-Mail')
        ->call('save')
        ->assertHasNoErrors();

    expect(FallbackProcess::first()->systems)->toBeEmpty();
});

test('fallback processes are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    FallbackProcess::factory()->for($company)->create(['title' => 'eigen']);
    FallbackProcess::factory()->for($other)->create(['title' => 'fremd']);

    $this->actingAs($user->fresh());

    expect(FallbackProcess::pluck('title')->all())->toBe(['eigen']);
    expect(FallbackProcess::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

test('user can delete a fallback process', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $process = FallbackProcess::factory()->for($company)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::fallback-processes.index')
        ->call('confirmDelete', $process->id)
        ->call('delete');

    expect(FallbackProcess::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(0);
});

test('editing preserves system links via sync', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $sys1 = System::factory()->for($company)->create();
    $sys2 = System::factory()->for($company)->create();

    $process = FallbackProcess::factory()->for($company)->create();
    $process->systems()->attach([$sys1->id]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::fallback-processes.index')
        ->call('openEdit', $process->id)
        ->set('system_ids', [$sys2->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($process->fresh()->systems->pluck('id')->all())->toBe([$sys2->id]);
});
