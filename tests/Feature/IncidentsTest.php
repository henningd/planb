<?php

use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('incidents index page renders', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh())
        ->get(route('incidents.index'))
        ->assertOk()
        ->assertSee('Vorfälle & Meldepflichten');
});

test('incident show page lists all applicable obligations with deadlines', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $report = IncidentReport::create([
        'company_id' => $company->id,
        'title' => 'Ransomware Buchhaltung',
        'type' => IncidentType::CyberAttack->value,
        'occurred_at' => now(),
    ]);

    foreach (ReportingObligation::applicableFor(IncidentType::CyberAttack->value) as $obligation) {
        $report->obligations()->create(['obligation' => $obligation->value]);
    }

    $this->actingAs($user->fresh())
        ->get(route('incidents.show', $report))
        ->assertOk()
        ->assertSee('Ransomware Buchhaltung')
        ->assertSee('DSGVO-Meldung an Aufsichtsbehörde')
        ->assertSee('Cyberversicherung benachrichtigen');
});

test('reporting obligation deadlines are correctly calculated', function () {
    expect(ReportingObligation::DsgvoNotification->deadlineHours())->toBe(72)
        ->and(ReportingObligation::Nis2EarlyWarning->deadlineHours())->toBe(24)
        ->and(ReportingObligation::CyberInsurance->deadlineHours())->toBeNull();

    expect(ReportingObligation::applicableFor('data_breach'))->toContain(
        ReportingObligation::DsgvoNotification,
        ReportingObligation::CyberInsurance,
    );
});
