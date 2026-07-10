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
        ->assertSee('4 Stunden');
});

test('a business process can store an Ersatzprozess (fallback)', function () {
    [$user] = bpActingUser();

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->set('name', 'Medikamentengabe')
        ->set('criticality', ProcessCriticality::Existenzkritisch->value)
        ->set('fallback_process', 'Papier-Betäubungsmittelbuch, manuelle Ausgabe durch PDL')
        ->call('save')
        ->assertHasNoErrors();

    expect(BusinessProcess::firstWhere('name', 'Medikamentengabe')->fallback_process)
        ->toBe('Papier-Betäubungsmittelbuch, manuelle Ausgabe durch PDL');
});

test('a business process stores review dates and flags an overdue review', function () {
    [$user, $company] = bpActingUser();

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->set('name', 'Lohnabrechnung')
        ->set('criticality', ProcessCriticality::Hoch->value)
        ->set('last_reviewed_at', '2026-01-15')
        ->set('next_review_at', '2026-07-15')
        ->call('save')
        ->assertHasNoErrors();

    $process = BusinessProcess::firstWhere('name', 'Lohnabrechnung');
    expect($process->last_reviewed_at->toDateString())->toBe('2026-01-15')
        ->and($process->next_review_at->toDateString())->toBe('2026-07-15');

    $overdue = BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Überfälliger Prozess',
        'next_review_at' => now()->subWeek()->toDateString(),
    ]);
    expect($overdue->isReviewOverdue())->toBeTrue();

    $this->actingAs($user)
        ->get(route('business-processes.index'))
        ->assertSee('überfällig');
});

test('a process can be created through the Livewire component including system assignment', function () {
    [$user, $company, $system] = bpActingUser();

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->set('name', 'Rechnungsstellung')
        ->set('criticality', ProcessCriticality::Hoch->value)
        ->set('mtpd_hours', '8')
        ->set('rto_hours', '2')
        ->set('rpo_hours', '1.5')
        ->set('selectedSystems', [$system->id])
        ->call('save')
        ->assertHasNoErrors();

    $process = BusinessProcess::firstWhere('name', 'Rechnungsstellung');

    expect($process)->not->toBeNull()
        ->and($process->company_id)->toBe($company->id)
        ->and($process->criticality)->toBe(ProcessCriticality::Hoch)
        ->and($process->mtpd_minutes)->toBe(480)
        ->and($process->rto_minutes)->toBe(120)
        ->and($process->rpo_minutes)->toBe(90)
        ->and($process->systems()->where('systems.id', $system->id)->exists())->toBeTrue();
});

test('recovery objectives entered in hours round-trip back into the edit form', function () {
    [$user, $company] = bpActingUser();

    $process = BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Versand',
        'mtpd_minutes' => 90,
        'rto_minutes' => 240,
        'rpo_minutes' => null,
    ]);

    Livewire::actingAs($user)
        ->test('pages::business-processes.index')
        ->call('openEdit', $process->id)
        ->assertSet('mtpd_hours', '1.5')
        ->assertSet('rto_hours', '4')
        ->assertSet('rpo_hours', null);
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
        ->assertSee('24 Stunden')
        ->assertSee($system->name);
});
