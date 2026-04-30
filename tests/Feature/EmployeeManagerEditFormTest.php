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
        ->test('pages::employees.edit', ['employee' => $lehmann]);

    expect($component->get('manager_ids'))
        ->toHaveCount(9)
        ->not->toContain($roth->id);
});

it('renders each manager-candidate checkbox with a stable wire:key', function () {
    // Ohne wire:key recycelt morphdom die <input>-Elemente positionsbasiert.
    // managerOptions filtert die aktuell bearbeitete Person raus, also
    // verschieben sich die Listenpositionen — ohne wire:key auf jedem
    // Wrapper-<label> würden Checked-States am DOM-Element kleben, das
    // jetzt einen anderen Kandidaten repräsentiert.
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $bernd = mkEmp($company, 'Bernd', 'Lehmann');
    $roth = mkEmp($company, 'Martin', 'Roth');

    $component = Livewire::actingAs($user->fresh())
        ->test('pages::employees.edit', ['employee' => $bernd]);

    $html = $component->html();

    expect($html)->toContain('wire:key="manager-option-'.$roth->id.'"');
});
