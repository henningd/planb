<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\Scenario;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\AssignmentSync;
use App\Support\Backup\Exporter;
use App\Support\Backup\Importer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('export contains the data of the current company only', function () {
    $userA = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create(['name' => 'Firma A']);
    Location::factory()->for($companyA)->create(['name' => 'Hauptsitz A']);

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create(['name' => 'Firma B']);
    Location::factory()->for($companyB)->create(['name' => 'Hauptsitz B']);

    $payload = Exporter::export($companyA, ['locations']);

    $names = collect($payload['areas']['locations'])->pluck('name')->all();
    expect($names)->toContain('Hauptsitz A');
    expect($names)->not->toContain('Hauptsitz B');
});

test('export includes nested scenario steps', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = $company->scenarios()->create([
        'name' => 'Stromausfall-Test',
        'description' => 'd',
        'trigger' => 't',
    ]);
    $scenario->steps()->create(['sort' => 0, 'title' => 'Schritt 1', 'description' => 'd', 'responsible' => 'IT']);

    $payload = Exporter::export($company, ['scenarios']);

    expect($payload['areas']['scenarios'])->toHaveCount(Scenario::withoutGlobalScope(CurrentCompanyScope::class)->where('company_id', $company->id)->count());
    expect($payload['areas']['_nested_scenarios_scenario_steps'])->not->toBeEmpty();
    $titles = collect($payload['areas']['_nested_scenarios_scenario_steps'])->pluck('title')->all();
    expect($titles)->toContain('Schritt 1');
});

test('roundtrip restores location data after wipe', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Location::factory()->for($company)->create(['name' => 'Original-Standort']);

    $payload = Exporter::export($company, ['locations']);

    DB::table('locations')->where('company_id', $company->id)->delete();
    expect(Location::withoutGlobalScope(CurrentCompanyScope::class)->where('company_id', $company->id)->count())->toBe(0);

    $summary = Importer::import($company, $payload, ['locations']);

    expect($summary['locations']['inserted'] ?? 0)->toBeGreaterThan(0);
    $names = Location::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->pluck('name')
        ->all();
    expect($names)->toContain('Original-Standort');
});

test('import enforces current company_id even if payload references another', function () {
    $userTarget = User::factory()->create();
    $target = Company::factory()->for($userTarget->currentTeam)->create();

    // Payload mit fremder company_id
    $payload = [
        'version' => 1,
        'areas' => [
            'locations' => [[
                'id' => '019dc999-1111-7000-8000-000000000001',
                'company_id' => '00000000-0000-0000-0000-000000000999',
                'name' => 'Cross-Tenant-Standort',
                'street' => 'X', 'postal_code' => '12345', 'city' => 'X', 'country' => 'DE',
                'is_headquarters' => false, 'phone' => null, 'notes' => null, 'sort' => 0,
                'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString(),
            ]],
        ],
    ];

    Importer::import($target, $payload, ['locations']);

    $row = DB::table('locations')->where('name', 'Cross-Tenant-Standort')->first();
    expect($row)->not->toBeNull();
    expect($row->company_id)->toBe($target->id);
});

test('replace mode wipes existing rows of selected areas before insert', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Alt-Rolle',
        'sort' => 99,
    ]);

    $payload = [
        'version' => 1,
        'areas' => [
            'roles' => [[
                'id' => '019dc999-2222-7000-8000-000000000001',
                'company_id' => $company->id,
                'name' => 'Neu-Rolle',
                'system_key' => null,
                'description' => null,
                'sort' => 0,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]],
        ],
    ];

    Importer::import($company, $payload, ['roles']);

    $names = Role::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->pluck('name')
        ->all();
    expect($names)->toContain('Neu-Rolle');
    expect($names)->not->toContain('Alt-Rolle');
});

test('company area is updated in-place, not deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'name' => 'Alt-Name',
        'employee_count' => 10,
    ]);

    $payload = [
        'version' => 1,
        'areas' => [
            'company' => [[
                'id' => '00000000-aaaa-aaaa-aaaa-000000000000',
                'team_id' => 999_999,
                'name' => 'Neu-Name',
                'industry' => $company->industry->value,
                'employee_count' => 42,
                'locations_count' => $company->locations_count ?? 1,
                'review_cycle_months' => 6,
                'last_reviewed_at' => null,
                'last_reminder_sent_at' => null,
                'legal_form' => $company->legal_form->value,
                'kritis_relevant' => $company->kritis_relevant->value,
                'nis2_classification' => $company->nis2_classification->value,
                'valid_from' => null,
                'cyber_insurance_deductible' => null,
                'budget_it_lead' => null,
                'budget_emergency_officer' => null,
                'budget_management' => null,
                'data_protection_authority_name' => null,
                'data_protection_authority_phone' => null,
                'data_protection_authority_website' => null,
            ]],
        ],
    ];

    Importer::import($company, $payload, ['company']);

    $fresh = Company::withoutGlobalScope(CurrentCompanyScope::class)->find($company->id);
    expect($fresh)->not->toBeNull();
    expect($fresh->name)->toBe('Neu-Name');
    expect($fresh->team_id)->toBe($company->team_id); // team_id darf nicht überschrieben werden
    expect($fresh->employee_count)->toBe(42);
});

test('pivot employee_role roundtrips through export/import', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $emp = Employee::factory()->for($company)->create();
    $role = Role::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Test-Rolle',
        'sort' => 99,
    ]);

    AssignmentSync::attach($role, $role->employees(), $emp->id);

    $payload = Exporter::export($company, ['employees', 'roles', 'pivot_employee_role']);

    expect($payload['areas']['pivot_employee_role'])->toHaveCount(1);

    // Wipe + Restore
    DB::table('employee_role')->delete();
    DB::table('roles')->where('company_id', $company->id)->whereNull('system_key')->delete();
    DB::table('employees')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, ['employees', 'roles', 'pivot_employee_role']);

    $restored = DB::table('employee_role')
        ->where('role_id', $role->id)
        ->where('employee_id', $emp->id)
        ->whereNull('removed_at')
        ->first();

    expect($restored)->not->toBeNull();
    // assigned_by_user_id wurde gestrippt → null
    expect($restored->assigned_by_user_id)->toBeNull();
});

test('system_dependencies roundtrip via parent-table filter', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $sysA = System::factory()->for($company)->create(['name' => 'Sys A']);
    $sysB = System::factory()->for($company)->create(['name' => 'Sys B']);

    DB::table('system_dependencies')->insert([
        'system_id' => $sysA->id,
        'depends_on_system_id' => $sysB->id,
        'sort' => 0,
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payload = Exporter::export($company, ['systems', 'system_dependencies']);
    expect($payload['areas']['system_dependencies'])->toHaveCount(1);

    DB::table('system_dependencies')->delete();
    DB::table('systems')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, ['systems', 'system_dependencies']);

    $row = DB::table('system_dependencies')
        ->where('system_id', $sysA->id)
        ->where('depends_on_system_id', $sysB->id)
        ->first();
    expect($row)->not->toBeNull();
});

test('scenario_runs export strips user IDs on import', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $scenario = $company->scenarios()->create(['name' => 'S', 'description' => '', 'trigger' => '']);

    $runId = (string) Str::uuid();
    DB::table('scenario_runs')->insert([
        'id' => $runId,
        'company_id' => $company->id,
        'scenario_id' => $scenario->id,
        'started_by_user_id' => $user->id,
        'title' => 'Run-1',
        'mode' => 'drill',
        'started_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('scenario_run_steps')->insert([
        'id' => (string) Str::uuid(),
        'scenario_run_id' => $runId,
        'sort' => 0,
        'title' => 'St-1',
        'description' => 'd',
        'responsible' => 'IT',
        'checked_at' => now(),
        'checked_by_user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payload = Exporter::export($company, ['scenarios', 'scenario_runs']);
    expect($payload['areas']['scenario_runs'])->toHaveCount(1);
    expect($payload['areas']['_nested_scenario_runs_scenario_run_steps'])->toHaveCount(1);

    DB::table('scenario_run_steps')->delete();
    DB::table('scenario_runs')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, ['scenarios', 'scenario_runs']);

    $run = DB::table('scenario_runs')->where('title', 'Run-1')->first();
    expect($run)->not->toBeNull();
    expect($run->started_by_user_id)->toBeNull();

    $step = DB::table('scenario_run_steps')->where('title', 'St-1')->first();
    expect($step->checked_by_user_id)->toBeNull();
});

test('admin can download backup and member cannot reach the route', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();
    Location::factory()->for($company)->create(['name' => 'TestLoc']);

    $response = $this->actingAs($owner->fresh())
        ->get(route('system-settings.backup.download', ['current_team' => $owner->currentTeam->slug]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/json');
    expect($response->streamedContent())->toContain('TestLoc');

    // Non-admin member darf nicht ran.
    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($member->fresh())
        ->get(route('system-settings.backup.download', ['current_team' => $owner->currentTeam->slug]))
        ->assertForbidden();
});
