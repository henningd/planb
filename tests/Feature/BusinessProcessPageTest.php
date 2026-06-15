<?php

use App\Enums\ProcessCriticality;
use App\Enums\SystemType;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function bpActingUser(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->create([
        'company_id' => $company->id,
        'system_type' => SystemType::Server,
    ]);

    return [$user->fresh(), $company, $system];
}

test('the business process page lists processes of the current company', function () {
    [$user, $company] = bpActingUser();

    BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Auftragsabwicklung',
        'criticality' => ProcessCriticality::Existenzkritisch,
        'mtpd_minutes' => 240,
    ]);

    $this->actingAs($user)
        ->get(route('business-processes.index'))
        ->assertOk()
        ->assertSee('Auftragsabwicklung')
        ->assertSee('Existenzkritisch')
        ->assertSee('240');
});

test('a process can be created through the Livewire component including system assignment', function () {
    [$user, $company, $system] = bpActingUser();

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->set('name', 'Rechnungsstellung')
        ->set('criticality', ProcessCriticality::Hoch->value)
        ->set('mtpd_minutes', '480')
        ->set('rto_minutes', '120')
        ->set('rpo_minutes', '60')
        ->set('selectedSystems', [$system->id])
        ->call('save')
        ->assertHasNoErrors();

    $process = BusinessProcess::firstWhere('name', 'Rechnungsstellung');

    expect($process)->not->toBeNull()
        ->and($process->company_id)->toBe($company->id)
        ->and($process->criticality)->toBe(ProcessCriticality::Hoch)
        ->and($process->mtpd_minutes)->toBe(480)
        ->and($process->systems()->where('systems.id', $system->id)->exists())->toBeTrue();
});

test('the criticality and recovery objectives are displayed on the list', function () {
    [$user, $company, $system] = bpActingUser();

    $process = BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Lohnbuchhaltung',
        'criticality' => ProcessCriticality::Mittel,
        'mtpd_minutes' => 1440,
        'rto_minutes' => 240,
    ]);
    $process->systems()->attach($system->id);

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->assertSee('Lohnbuchhaltung')
        ->assertSee('Mittel')
        ->assertSee('1440')
        ->assertSee($system->name);
});
