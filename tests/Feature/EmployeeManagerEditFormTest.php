<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function mkEmp(Company $company, string $first, string $last): Employee
{
    return Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => $first,
        'last_name' => $last,
    ]);
}

it('loads only the actual managers into manager_ids, never an unrelated employee', function () {
    // Reproduziert das Siegburg-Szenario: Lehmann hat 9 Manager, KEIN Roth.
    // Im Edit-Form darf Roth nicht in manager_ids landen.
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $alt = mkEmp($company, 'Leonhard', 'Alt');
    $burgemeister = mkEmp($company, 'Andreas', 'Burgemeister');
    $gerull = mkEmp($company, 'Jan', 'Gerull');
    $glathe = mkEmp($company, 'Carsten', 'Glathe');
    $greis = mkEmp($company, 'Max', 'Greis');
    $griesebock = mkEmp($company, 'Kevin', 'Griesebock');
    $kristahn = mkEmp($company, 'Björn', 'Kristahn');
    $langen = mkEmp($company, 'Marc', 'Langen');
    $lehmann = mkEmp($company, 'Bernd', 'Lehmann');
    $rosemann = mkEmp($company, 'Stefan', 'Rosemann');
    $roth = mkEmp($company, 'Martin', 'Roth');

    $roth->managers()->attach($burgemeister->id);
    $lehmann->managers()->sync([
        $greis->id, $griesebock->id, $langen->id, $rosemann->id,
        $gerull->id, $burgemeister->id, $kristahn->id, $glathe->id, $alt->id,
    ]);

    $component = Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->call('openEdit', $lehmann->id);

    expect($component->get('manager_ids'))
        ->toHaveCount(9)
        ->not->toContain($roth->id);
});

it('does not leak manager_ids between successive openEdit calls', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $a = mkEmp($company, 'Anna', 'A');
    $b = mkEmp($company, 'Bernd', 'B');
    $c = mkEmp($company, 'Carla', 'C');

    $a->managers()->attach($c->id);

    $component = Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->call('openEdit', $a->id);

    expect($component->get('manager_ids'))->toBe([$c->id]);

    $component->call('openEdit', $b->id);

    expect($component->get('manager_ids'))->toBe([]);
});

it('renders each manager-candidate checkbox with a stable wire:key', function () {
    // Ohne wire:key recycelt morphdom die <input>-Elemente positionsbasiert.
    // managerOptions filtert die aktuell bearbeitete Person raus, also
    // verschieben sich die Listenpositionen beim Wechsel zwischen Mitarbeitern
    // — und alte checked-States kleben am DOM-Element, das jetzt einen
    // anderen Kandidaten repräsentiert. wire:key auf jedem Wrapper-<label>
    // verhindert das.
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $bernd = mkEmp($company, 'Bernd', 'Lehmann');
    $roth = mkEmp($company, 'Martin', 'Roth');

    $component = Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->call('openEdit', $bernd->id);

    $html = $component->html();

    expect($html)->toContain('wire:key="manager-option-'.$roth->id.'"');
});
