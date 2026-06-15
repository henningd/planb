<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\PreventiveMeasure;
use App\Models\System;
use App\Models\User;
use App\Support\Backup\BackupCatalog;
use App\Support\Backup\Exporter;
use App\Support\Backup\Importer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('the backup catalog covers all content and BCMS areas', function () {
    $keys = array_keys(BackupCatalog::all());

    $expected = [
        'departments', 'fallback_processes', 'risks', 'risk_mitigations',
        'lessons_learned', 'lesson_learned_action_items', 'business_processes',
        'preventive_measures', 'supplier_risk_assessments', 'training_records',
        'maturity_assessments', 'bcm_policies', 'management_reviews',
        'pivot_risk_system', 'pivot_fallback_process_system', 'pivot_business_process_system',
    ];

    foreach ($expected as $key) {
        expect($keys)->toContain($key);
    }
});

test('a full export includes rows for the new standalone areas', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    DB::table('departments')->insert(['id' => (string) Str::uuid(), 'company_id' => $company->id, 'name' => 'IT', 'sort' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('bcm_policies')->insert(['id' => (string) Str::uuid(), 'company_id' => $company->id, 'scope' => 'Gesamt', 'content' => 'x', 'version' => '1.0', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('management_reviews')->insert(['id' => (string) Str::uuid(), 'company_id' => $company->id, 'title' => 'Review 1', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('maturity_assessments')->insert(['id' => (string) Str::uuid(), 'company_id' => $company->id, 'answers' => json_encode([]), 'score' => 0, 'stage' => 'reaktiv', 'assessed_at' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);

    $payload = Exporter::export($company, array_keys(BackupCatalog::all()));

    expect($payload['areas']['departments'])->toHaveCount(1)
        ->and($payload['areas']['bcm_policies'])->toHaveCount(1)
        ->and($payload['areas']['management_reviews'])->toHaveCount(1)
        ->and($payload['areas']['maturity_assessments'])->toHaveCount(1);
});

test('risks, mitigations and risk-system links roundtrip through export/import', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->for($company)->create(['name' => 'ERP']);
    $employee = Employee::factory()->for($company)->create();

    $riskId = (string) Str::uuid();
    DB::table('risks')->insert([
        'id' => $riskId, 'company_id' => $company->id, 'title' => 'Ransomware',
        'probability' => 4, 'impact' => 5, 'category' => 'operational', 'status' => 'identified',
        'owner_user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('risk_mitigations')->insert([
        'id' => (string) Str::uuid(), 'risk_id' => $riskId, 'title' => 'Offline-Backups',
        'status' => 'planned', 'responsible_employee_id' => $employee->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('risk_system')->insert(['risk_id' => $riskId, 'system_id' => $system->id]);

    $areas = ['systems', 'employees', 'risks', 'risk_mitigations', 'pivot_risk_system'];
    $payload = Exporter::export($company, $areas);

    expect($payload['areas']['risks'])->toHaveCount(1)
        ->and($payload['areas']['risk_mitigations'])->toHaveCount(1)
        ->and($payload['areas']['pivot_risk_system'])->toHaveCount(1);

    // Wipe (mitigations + pivot zuerst wegen FK), dann restore.
    DB::table('risk_system')->delete();
    DB::table('risk_mitigations')->delete();
    DB::table('risks')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, $areas);

    expect(DB::table('risks')->where('title', 'Ransomware')->exists())->toBeTrue()
        ->and(DB::table('risk_mitigations')->where('title', 'Offline-Backups')->value('responsible_employee_id'))->toBe($employee->id)
        ->and(DB::table('risk_system')->where('risk_id', $riskId)->where('system_id', $system->id)->exists())->toBeTrue();

    // owner_user_id wurde beim Insert gestrippt.
    expect(DB::table('risks')->where('title', 'Ransomware')->value('owner_user_id'))->toBeNull();
});

test('preventive measures roundtrip through export/import', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->for($company)->create(['name' => 'Fileserver']);

    PreventiveMeasure::factory()->forSystem($system)->create(['title' => 'Backup-Test']);

    $areas = ['systems', 'preventive_measures'];
    $payload = Exporter::export($company, $areas);
    expect($payload['areas']['preventive_measures'])->toHaveCount(1);

    DB::table('preventive_measures')->delete();
    DB::table('systems')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, $areas);

    expect(DB::table('preventive_measures')->where('title', 'Backup-Test')->value('system_id'))->toBe($system->id);
});

test('business process to system links roundtrip through export/import', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $system = System::factory()->for($company)->create(['name' => 'CRM']);

    $processId = (string) Str::uuid();
    DB::table('business_processes')->insert([
        'id' => $processId, 'company_id' => $company->id, 'name' => 'Auftragsannahme',
        'criticality' => 'hoch', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('business_process_system')->insert([
        'business_process_id' => $processId, 'system_id' => $system->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $areas = ['systems', 'business_processes', 'pivot_business_process_system'];
    $payload = Exporter::export($company, $areas);
    expect($payload['areas']['pivot_business_process_system'])->toHaveCount(1);

    DB::table('business_process_system')->delete();
    DB::table('business_processes')->where('company_id', $company->id)->delete();

    Importer::import($company, $payload, $areas);

    expect(DB::table('business_process_system')->where('business_process_id', $processId)->where('system_id', $system->id)->exists())->toBeTrue();
});
