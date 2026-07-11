<?php

use App\Enums\InsuranceType;
use App\Enums\ProcessCriticality;
use App\Models\AiSystem;
use App\Models\AuthorityContact;
use App\Models\BusinessProcess;
use App\Models\Company;
use App\Models\Employee;
use App\Models\InsurancePolicy;
use App\Models\LessonLearned;
use App\Models\LessonLearnedActionItem;
use App\Models\ManagementReview;
use App\Models\OpenItem;
use App\Models\PreventiveMeasure;
use App\Models\Risk;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the audit report renders processes with their linked governance items', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'Pflegedokumentation',
        'category' => 'geschaeftsbetrieb',
    ]);

    $process = BusinessProcess::factory()->create([
        'company_id' => $company->id,
        'name' => 'Medikamentengabe',
        'criticality' => ProcessCriticality::Existenzkritisch,
        'rto_minutes' => 240,
        'next_review_at' => '2026-12-01',
    ]);
    $process->systems()->attach($system->id);

    Risk::factory()->create([
        'company_id' => $company->id,
        'business_process_id' => $process->id,
        'title' => 'Ransomware-Befall Pflegeserver',
    ]);
    PreventiveMeasure::factory()->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'business_process_id' => $process->id,
        'title' => 'Backup-Restore-Test',
    ]);
    OpenItem::factory()->create([
        'company_id' => $company->id,
        'business_process_id' => $process->id,
        'title' => 'Break-Glass-Zugang ungetestet',
    ]);

    // Nicht zugeordnetes Risiko landet im Anhang.
    Risk::factory()->create([
        'company_id' => $company->id,
        'title' => 'Freistehendes Risiko',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('Audit-/Governance-Bericht')
        ->assertSee('Medikamentengabe')
        ->assertSee('Pflegedokumentation')
        ->assertSee('Ransomware-Befall Pflegeserver')
        ->assertSee('Backup-Restore-Test')
        ->assertSee('Break-Glass-Zugang ungetestet')
        ->assertSee('Anhang: Nicht zugeordnet')
        ->assertSee('Freistehendes Risiko');
});

test('the audit report requires a company', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('audit-report.print'))
        ->assertNotFound();
});

test('the audit report includes an insurance review section', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber->value,
        'insurer' => 'CyberProtect AG',
        'coverage_amount' => '5 Mio €',
        'valid_until' => '2020-01-01',
        'next_review_at' => '2020-06-01',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('Versicherungen und Schadenabsicherung')
        ->assertSee('CyberProtect AG')
        ->assertSee('5 Mio €')
        ->assertSee('abgelaufen')
        ->assertSee('überfällig')
        ->assertSee('nicht getestet');
});

test('the audit report includes a maturity overview, authorities and the training organizer', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $trained = Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Erika', 'last_name' => 'Musterfrau']);
    $organizer = Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Olaf', 'last_name' => 'Organisator']);
    TrainingRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $trained->id,
        'responsible_employee_id' => $organizer->id,
        'topic' => 'Brandschutzunterweisung',
    ]);

    AuthorityContact::factory()->create([
        'company_id' => $company->id,
        'name' => 'Landesumweltamt Musterland',
        'occasion' => 'Umweltrelevanter Störfall',
        'phone' => '0800 111222',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('Reifegrad / Überblick')
        ->assertSee('Behörden, Meldestellen und externe Stellen')
        ->assertSee('Landesumweltamt Musterland')
        ->assertSee('Brandschutzunterweisung')
        ->assertSee('Olaf Organisator');
});

test('the audit report includes training, tasks, lessons and management review', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'first_name' => 'Max',
        'last_name' => 'Muster',
    ]);
    TrainingRecord::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $employee->id,
        'topic' => 'Notfall-Erstschulung',
    ]);

    $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);
    SystemTask::factory()->create([
        'company_id' => $company->id,
        'system_id' => $system->id,
        'title' => 'Backup-Konzept prüfen',
    ]);

    $lesson = LessonLearned::factory()->create([
        'company_id' => $company->id,
        'title' => 'Kommunikation zu langsam',
    ]);
    LessonLearnedActionItem::factory()->create([
        'lesson_learned_id' => $lesson->id,
        'description' => 'Alarmkette aktualisieren',
    ]);

    ManagementReview::factory()->create([
        'company_id' => $company->id,
        'title' => 'Jahres-Review 2026',
        'decisions' => 'Budget für BCM freigegeben',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('Schulungen und Awareness')
        ->assertSee('Notfall-Erstschulung')
        ->assertSee('Aufgaben und Nachverfolgung')
        ->assertSee('Backup-Konzept prüfen')
        ->assertSee('Lessons Learned')
        ->assertSee('Kommunikation zu langsam')
        ->assertSee('Alarmkette aktualisieren')
        ->assertSee('Management Review / BCMS-Governance')
        ->assertSee('Jahres-Review 2026')
        ->assertSee('Budget für BCM freigegeben');
});

test('the audit report includes an ai systems section', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    AiSystem::factory()->highRisk()->create([
        'company_id' => $company->id,
        'name' => 'Bewerber-Ranking',
        'next_review_at' => '2020-01-01',
    ]);

    $this->actingAs($user->fresh())
        ->get(route('audit-report.print'))
        ->assertOk()
        ->assertSee('KI-Systeme (EU-KI-Verordnung)')
        ->assertSee('Bewerber-Ranking')
        ->assertSee('Hochrisiko')
        ->assertSee('überfällig');
});
