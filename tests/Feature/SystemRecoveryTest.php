<?php

use App\Models\Company;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\RecoveryOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function bootRecoveryTenant(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

function makeSystem(Company $company, string $name, int $prioritySort = 1): System
{
    $priority = $company->systemPriorities()->where('sort', $prioritySort)->first();

    return System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => $name,
        'category' => 'basisbetrieb',
        'system_priority_id' => $priority?->id,
    ]);
}

test('recovery order puts dependency-free systems first', function () {
    [, $company] = bootRecoveryTenant();

    $storage = makeSystem($company, 'Storage');
    $database = makeSystem($company, 'Datenbank');
    $erp = makeSystem($company, 'ERP');

    $database->dependencies()->sync([$storage->id]);
    $erp->dependencies()->sync([$database->id]);

    $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->with(['priority', 'dependencies'])
        ->get();

    $plan = RecoveryOrder::compute($systems);

    expect($plan['cycles'])->toBeEmpty()
        ->and(collect($plan['stages'])->map(fn ($s) => collect($s)->pluck('name')->all())->all())
        ->toBe([
            ['Storage'],
            ['Datenbank'],
            ['ERP'],
        ]);
});

test('systems on the same stage are sorted by priority then name', function () {
    [, $company] = bootRecoveryTenant();

    makeSystem($company, 'Telefonanlage', 3);
    makeSystem($company, 'Firewall', 1);
    makeSystem($company, 'Drucker', 2);

    $systems = System::withoutGlobalScope(CurrentCompanyScope::class)
        ->with(['priority', 'dependencies'])
        ->get();

    $plan = RecoveryOrder::compute($systems);

    $names = collect($plan['stages'][0])->pluck('name')->all();

    expect($names)->toBe(['Firewall', 'Drucker', 'Telefonanlage']);
});

test('edit form rejects dependencies that would create a cycle', function () {
    [$user, $company] = bootRecoveryTenant();

    $a = makeSystem($company, 'A');
    $b = makeSystem($company, 'B');

    $b->dependencies()->sync([$a->id]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.index')
        ->call('openEdit', $a->id)
        ->set('depends_on_ids', [$b->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->dependencies)->toBeEmpty()
        ->and($b->fresh()->dependencies->pluck('id')->all())->toBe([$a->id]);
});

test('recovery page lists stages', function () {
    [$user, $company] = bootRecoveryTenant();

    $storage = makeSystem($company, 'Storage-Cluster');
    $db = makeSystem($company, 'Datenbank-Host');
    $db->dependencies()->sync([$storage->id]);

    $this->actingAs($user)
        ->get(route('systems.recovery'))
        ->assertOk()
        ->assertSee('Wiederanlauf-Reihenfolge')
        ->assertSee('Storage-Cluster')
        ->assertSee('Datenbank-Host')
        ->assertSee('Stufe 1')
        ->assertSee('Stufe 2');
});

test('recovery page empty state when no systems exist', function () {
    [$user] = bootRecoveryTenant();

    $this->actingAs($user)
        ->get(route('systems.recovery'))
        ->assertOk()
        ->assertSee('Noch keine Systeme erfasst.');
});

test('system form saves selected dependencies', function () {
    [$user, $company] = bootRecoveryTenant();

    $storage = makeSystem($company, 'Storage');
    $db = makeSystem($company, 'Datenbank');

    Livewire\Livewire::actingAs($user)
        ->test('pages::systems.index')
        ->call('openEdit', $db->id)
        ->set('depends_on_ids', [$storage->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($db->fresh()->dependencies->pluck('name')->all())->toBe(['Storage']);
});
