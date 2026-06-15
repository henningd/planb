<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\System;
use App\Models\User;
use App\Support\Tenant\CompanyReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('reset wipes all tenant data but keeps users, team and company profile', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Test GmbH']);

    $system = System::factory()->for($company)->create(['name' => 'ERP']);
    Employee::factory()->for($company)->create();

    // Risiko + Maßnahme + Pivot — Maßnahme/Pivot haben KEINE company_id,
    // prüft also die DB-Cascade über die Eltern-Tabelle.
    $riskId = (string) Str::uuid();
    DB::table('risks')->insert(['id' => $riskId, 'company_id' => $company->id, 'title' => 'R', 'probability' => 3, 'impact' => 3, 'category' => 'operational', 'status' => 'identified', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('risk_mitigations')->insert(['id' => (string) Str::uuid(), 'risk_id' => $riskId, 'title' => 'M', 'status' => 'planned', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('risk_system')->insert(['risk_id' => $riskId, 'system_id' => $system->id]);

    $oldId = $company->id;
    $teamId = $company->team_id;

    $new = CompanyReset::run($company);

    // Neue Firma, gleiches Team + Profil
    expect($new->id)->not->toBe($oldId)
        ->and($new->team_id)->toBe($teamId)
        ->and($new->name)->toBe('Test GmbH');

    // Alte Firma + alle Daten weg (Hard-Delete + Cascade auch auf id-lose Kinder)
    expect(Company::withTrashed()->find($oldId))->toBeNull()
        ->and(DB::table('systems')->where('company_id', $oldId)->count())->toBe(0)
        ->and(DB::table('employees')->where('company_id', $oldId)->count())->toBe(0)
        ->and(DB::table('risks')->where('id', $riskId)->count())->toBe(0)
        ->and(DB::table('risk_mitigations')->count())->toBe(0)
        ->and(DB::table('risk_system')->count())->toBe(0);

    // App-Benutzer + Team unberührt, currentCompany zeigt auf die neue Firma
    expect(User::find($user->id))->not->toBeNull()
        ->and($user->fresh()->currentCompany()?->id)->toBe($new->id);

    // Frische Default-Stammdaten via CompanyObserver
    expect(DB::table('system_priorities')->where('company_id', $new->id)->count())->toBe(3)
        ->and(DB::table('emergency_levels')->where('company_id', $new->id)->count())->toBe(3);
});

test('an admin resets the team through the settings page with the correct name', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Reset GmbH']);
    System::factory()->for($company)->create();
    $oldId = $company->id;

    Livewire::actingAs($user->fresh())
        ->test('pages::system-settings.index')
        ->set('resetConfirmName', 'Reset GmbH')
        ->call('resetTenant')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Company::withTrashed()->find($oldId))->toBeNull()
        ->and(DB::table('systems')->where('company_id', $oldId)->count())->toBe(0)
        ->and($user->fresh()->currentCompany()?->name)->toBe('Reset GmbH');
});

test('a wrong company name does not reset the team', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Reset GmbH']);
    $oldId = $company->id;

    Livewire::actingAs($user->fresh())
        ->test('pages::system-settings.index')
        ->set('resetConfirmName', 'Falscher Name')
        ->call('resetTenant')
        ->assertHasErrors('resetConfirmName');

    expect(Company::find($oldId))->not->toBeNull();
});
