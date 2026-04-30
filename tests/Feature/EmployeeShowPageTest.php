<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('detail page shows core data, hierarchy, roles and systems', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $manager = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Chefin',
        'last_name' => 'Vorgesetzte',
    ]);

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Beispiel',
        'position' => 'Vertriebsleitung',
        'mobile_phone' => '0171 1234567',
        'email' => 'anna@example.test',
        'emergency_contact' => 'Ehemann Max · 0172 9999999',
        'is_key_personnel' => true,
        'notes' => 'Spricht fließend Französisch.',
    ]);
    $employee->managers()->attach($manager->id);

    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Werkstattleitung',
        'sort' => 0,
    ]);
    AssignmentSync::attach($employee, $employee->roles(), $role->id, ['is_deputy' => true]);

    $system = System::factory()->for($company)->create(['name' => 'Warenwirtschaft']);
    AssignmentSync::attach($system, $system->employees(), $employee->id, [
        'ownership_kind' => 'owner',
        'is_deputy' => false,
        'note' => 'Hauptverantwortliche für Stammdaten.',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('Beispiel, Anna')
        ->assertSee('Vertriebsleitung')
        ->assertSee('Schlüsselmitarbeiter')
        ->assertSee('0171 1234567')
        ->assertSee('anna@example.test')
        ->assertSee('Vorgesetzte, Chefin')
        ->assertSee('Werkstattleitung')
        ->assertSee('Stellvertretung')
        ->assertSee('Warenwirtschaft')
        ->assertSee('Eigentümer')
        ->assertSee('Hauptverantwortliche für Stammdaten.')
        ->assertSee('Spricht fließend Französisch.');
});

test('detail header shows only system tags alphabetically, no role tags at all', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Reihenfolge',
        'last_name' => 'Test',
    ]);

    // Custom- UND Pflichtrolle: beide dürfen NICHT als Header-Tag erscheinen,
    // sondern nur in der Sektion „Rollen" weiter unten.
    $custom = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'NurInRollenSektion', 'sort' => 0,
    ]);
    AssignmentSync::attach($employee, $employee->roles(), $custom->id, ['is_deputy' => false]);

    $mandatory = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::EmergencyOfficer->value)
        ->firstOrFail();
    AssignmentSync::attach($employee, $employee->roles(), $mandatory->id, ['is_deputy' => false]);

    $sysA = System::factory()->for($company)->create(['name' => 'AlphaSystem']);
    $sysB = System::factory()->for($company)->create(['name' => 'BetaSystem']);
    foreach ([$sysA, $sysB] as $sys) {
        AssignmentSync::attach($sys, $sys->employees(), $employee->id, [
            'ownership_kind' => 'operator', 'is_deputy' => false, 'note' => '',
        ]);
    }

    $body = $this->actingAs($user->fresh())->get(route('employees.show', $employee))->assertOk()->getContent();

    // Header endet vor der ersten <flux:heading>-Sektion „Kontakt".
    $headerEnd = strpos($body, 'Kontakt');
    expect($headerEnd)->not->toBeFalse();
    $header = substr($body, 0, $headerEnd);

    // Keine Rollen-Tags im Header — weder Pflicht- noch Custom-Rollen.
    expect($header)->not->toContain('Notfallbeauftragte/r')->not->toContain('NurInRollenSektion');

    // System-Badges alphabetisch im Header.
    $alphaSysPos = strpos($header, 'AlphaSystem');
    $betaSysPos = strpos($header, 'BetaSystem');
    expect($alphaSysPos)->not->toBeFalse()->and($betaSysPos)->not->toBeFalse();
    expect($alphaSysPos)->toBeLessThan($betaSysPos);
});

test('index card shows only system tags, hides all role tags', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Karten',
        'last_name' => 'Test',
    ]);
    $custom = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'KartenCustomRolle', 'sort' => 0,
    ]);
    AssignmentSync::attach($employee, $employee->roles(), $custom->id, ['is_deputy' => false]);

    $mandatory = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('system_key', CrisisRole::EmergencyOfficer->value)
        ->firstOrFail();
    AssignmentSync::attach($employee, $employee->roles(), $mandatory->id, ['is_deputy' => false]);

    $system = System::factory()->for($company)->create(['name' => 'KartenSystem']);
    AssignmentSync::attach($system, $system->employees(), $employee->id, [
        'ownership_kind' => 'operator', 'is_deputy' => false, 'note' => '',
    ]);

    $body = $this->actingAs($user->fresh())->get(route('employees.index'))->assertOk()->getContent();
    $cardStart = strpos($body, 'Test, Karten');
    expect($cardStart)->not->toBeFalse();

    // Ab Karten-Heading: weder Custom- noch Pflichtrolle als Tag in der Karte.
    expect(strpos($body, 'KartenCustomRolle', $cardStart))->toBeFalse();
    expect(strpos($body, 'Notfallbeauftragte/r', $cardStart))->toBeFalse();

    // Aber das System-Tag ist da.
    expect(strpos($body, 'KartenSystem', $cardStart))->not->toBeFalse();
});

test('systems section also lists systems where the employee only has task assignments', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Aufgaben',
        'last_name' => 'Bearbeiter',
    ]);

    // System ohne direkte Mitarbeiter-Zuordnung — Mitarbeiter ist nur über
    // eine Aufgabe verbunden.
    $system = System::factory()->for($company)->create(['name' => 'NurUeberAufgaben']);
    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Wichtige Wartungs-Aufgabe',
    ]);
    AssignmentSync::attach($task, $task->assignees(), $employee->id, [
        'raci_role' => 'A', 'is_deputy' => false,
    ]);

    expect($employee->systems()->count())->toBe(0);

    $body = $this->actingAs($user->fresh())
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('NurUeberAufgaben')
        ->assertSee('Wichtige Wartungs-Aufgabe')
        ->assertSee('Hauptperson')
        ->assertSee('A · Verantwortlich')
        ->getContent();

    // Aufgabe steht innerhalb des System-Listeneintrags (nach dem System-Namen).
    $sysPos = strpos($body, 'NurUeberAufgaben');
    $taskPos = strpos($body, 'Wichtige Wartungs-Aufgabe');
    expect($sysPos)->toBeLessThan($taskPos);
});

test('systems section also lists tasks the employee inherits via a role', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Indirekt',
        'last_name' => 'Test',
    ]);

    // Mitarbeiter ist in Rolle "Backend-Team" (als Vertretung).
    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id, 'name' => 'Backend-Team', 'sort' => 0,
    ]);
    AssignmentSync::attach($employee, $employee->roles(), $role->id, ['is_deputy' => true]);

    // System mit einer Aufgabe — die Aufgabe ist NICHT dem Mitarbeiter direkt
    // zugeordnet, sondern der Rolle "Backend-Team".
    $system = System::factory()->for($company)->create(['name' => 'IndirektesSystem']);
    $task = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Indirekt-via-Rolle-Aufgabe',
    ]);
    AssignmentSync::attach($task, $task->roleAssignees(), $role->id, [
        'raci_role' => 'R',
    ]);

    // Direkte Mitarbeiter-System-Verbindung: keine. Direkte
    // Aufgaben-Mitarbeiter-Verbindung: keine. Nur via Rolle.
    expect($employee->systems()->count())->toBe(0);
    expect($employee->tasks()->count())->toBe(0);

    $body = $this->actingAs($user->fresh())
        ->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('IndirektesSystem')
        ->assertSee('Indirekt-via-Rolle-Aufgabe')
        ->assertSee('via Rolle Backend-Team')
        ->assertSee('R · Durchführend')
        ->getContent();

    // Die Quelle muss als „via Rolle" sichtbar sein, nicht als direkte
    // „Hauptperson"-Badge — sonst würde der User die Herkunft missverstehen.
    $taskPos = strpos($body, 'Indirekt-via-Rolle-Aufgabe');
    $sourcePos = strpos($body, 'via Rolle Backend-Team', $taskPos);
    expect($sourcePos)->not->toBeFalse();
});

test('detail page is reachable via the index dropdown', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Eva',
        'last_name' => 'Beispiel',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('employees.index'))
        ->assertOk()
        ->assertSee(route('employees.show', $employee), false)
        ->assertSee('Details');
});

test('delete from detail page removes the employee and redirects to the index', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Tobi',
        'last_name' => 'Tester',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::employees.show', ['employee' => $employee])
        ->call('delete')
        ->assertRedirect(route('employees.index'));

    expect(Employee::find($employee->id))->toBeNull();
});
