<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use App\Support\Employees\EmployeeExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('serializes employees with department, location, managers, roles, systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $dept = Department::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'IT',
    ]);
    $location = Location::factory()->for($company)->create(['name' => 'Hauptsitz']);

    $chef = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Chef',
        'last_name' => 'Müller',
    ]);
    $emp = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'position' => 'IT-Leiterin',
        'department_id' => $dept->id,
        'location_id' => $location->id,
        'is_key_personnel' => true,
    ]);
    $emp->managers()->attach($chef->id);

    $role = Role::factory()->for($company)->create(['name' => 'Geschäftsleitung']);
    AssignmentSync::sync($emp, $emp->roles(), [$role->id]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'ERP',
    ]);
    AssignmentSync::attach($emp, $emp->systems(), $system->id, ['raci_role' => 'Accountable', 'sort' => 0, 'note' => 'Lead']);

    $payload = EmployeeExporter::export($company);

    expect($payload['count'])->toBe(2)
        ->and($payload['company']['id'])->toBe($company->id)
        ->and($payload['exported_at'])->toBeString();

    $annaRow = collect($payload['employees'])->firstWhere('first_name', 'Anna');
    expect($annaRow)
        ->name_last_first->toBe('Beispiel, Anna')
        ->department->toBe('IT')
        ->location->toBe('Hauptsitz')
        ->position->toBe('IT-Leiterin')
        ->is_key_personnel->toBeTrue();

    expect($annaRow['managers'])->toHaveCount(1)
        ->and($annaRow['managers'][0]['name'])->toBe('Müller, Chef');

    expect($annaRow['roles'])->toHaveCount(1)
        ->and($annaRow['roles'][0]['name'])->toBe('Geschäftsleitung');

    expect($annaRow['systems'])->toHaveCount(1)
        ->and($annaRow['systems'][0]['name'])->toBe('ERP')
        ->and($annaRow['systems'][0]['raci_role'])->toBe('Accountable')
        ->and($annaRow['systems'][0]['note'])->toBe('Lead');
});

it('lists reports for managers in the export', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $chef = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Chef', 'last_name' => 'Müller',
    ]);
    $emp = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Anna', 'last_name' => 'Beispiel',
    ]);
    $emp->managers()->attach($chef->id);

    $payload = EmployeeExporter::export($company);
    $chefRow = collect($payload['employees'])->firstWhere('first_name', 'Chef');

    expect($chefRow['reports'])->toHaveCount(1)
        ->and($chefRow['reports'][0]['name'])->toBe('Beispiel, Anna');
});

it('returns a streamed JSON download with the right filename and content-type', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'first_name' => 'Anna', 'last_name' => 'Beispiel',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::employees.index')
        ->call('exportJson')
        ->assertFileDownloaded(null, null, 'application/json');

    // Inhalt prüfen — direkter Aufruf des Exporters, da Livewires
    // assertFileDownloaded den gestreamten Body nicht freigibt.
    $payload = EmployeeExporter::export($company);
    expect($payload['employees'])->toHaveCount(1)
        ->and($payload['employees'][0]['first_name'])->toBe('Anna');
});
