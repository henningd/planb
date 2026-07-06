<?php

use App\Enums\IncidentType;
use App\Enums\Industry;
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

test('municipal obligations have neutral labels and no fixed deadline', function () {
    expect(ReportingObligation::CertLandNotification->deadlineHours())->toBeNull()
        ->and(ReportingObligation::KommunalaufsichtNotification->deadlineHours())->toBeNull()
        ->and(ReportingObligation::CertLandNotification->label())->toBe('Meldung an Landes-CERT (empfohlen: unverzüglich)')
        ->and(ReportingObligation::KommunalaufsichtNotification->label())->toBe('Kommunal-/Rechtsaufsicht informieren (empfohlen: zeitnah)')
        ->and(ReportingObligation::CertLandNotification->isMunicipal())->toBeTrue()
        ->and(ReportingObligation::KommunalaufsichtNotification->isMunicipal())->toBeTrue()
        ->and(ReportingObligation::DsgvoNotification->isMunicipal())->toBeFalse();
});

test('municipal obligations only apply for public sector companies', function () {
    // Ohne Branche bzw. für nicht-kommunale Branchen: keine kommunalen Meldewege.
    foreach ([null, Industry::Handwerk, Industry::Handel] as $industry) {
        foreach (IncidentType::cases() as $type) {
            expect(ReportingObligation::applicableFor($type->value, $industry))
                ->not->toContain(ReportingObligation::CertLandNotification)
                ->not->toContain(ReportingObligation::KommunalaufsichtNotification);
        }
    }

    // Öffentliche Einrichtung: Landes-CERT bei sicherheitsrelevanten Vorfällen …
    foreach (['cyber_attack', 'data_breach', 'other'] as $type) {
        expect(ReportingObligation::applicableFor($type, Industry::OeffentlicheEinrichtung))
            ->toContain(ReportingObligation::CertLandNotification)
            ->toContain(ReportingObligation::KommunalaufsichtNotification);
    }

    // … beim reinen Systemausfall nur die Information der Aufsicht.
    expect(ReportingObligation::applicableFor('outage', Industry::OeffentlicheEinrichtung))
        ->not->toContain(ReportingObligation::CertLandNotification)
        ->toContain(ReportingObligation::KommunalaufsichtNotification);
});

test('existing obligation sets are unchanged for non-municipal companies', function () {
    expect(ReportingObligation::applicableFor('cyber_attack'))->toBe([
        ReportingObligation::DsgvoNotification,
        ReportingObligation::Nis2EarlyWarning,
        ReportingObligation::Nis2InitialReport,
        ReportingObligation::CyberInsurance,
        ReportingObligation::EmployeeNotification,
    ])->and(ReportingObligation::applicableFor('outage'))->toBe([
        ReportingObligation::CyberInsurance,
        ReportingObligation::EmployeeNotification,
    ]);
});

test('creating an incident for a public sector company adds municipal obligations', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create(['industry' => Industry::OeffentlicheEinrichtung]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::incidents.index')
        ->set('title', 'Ransomware Rathaus')
        ->set('type', IncidentType::CyberAttack->value)
        ->set('occurred_at', now()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertHasNoErrors();

    $report = IncidentReport::withoutGlobalScopes()->where('title', 'Ransomware Rathaus')->firstOrFail();
    $obligations = $report->obligations->map(fn ($o) => $o->obligation)->all();

    expect($obligations)->toContain(ReportingObligation::CertLandNotification)
        ->toContain(ReportingObligation::KommunalaufsichtNotification);
});

test('creating an incident for a non-municipal company adds no municipal obligations', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create(['industry' => Industry::Handwerk]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::incidents.index')
        ->set('title', 'Ransomware Werkstatt')
        ->set('type', IncidentType::CyberAttack->value)
        ->set('occurred_at', now()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertHasNoErrors();

    $report = IncidentReport::withoutGlobalScopes()->where('title', 'Ransomware Werkstatt')->firstOrFail();
    $obligations = $report->obligations->map(fn ($o) => $o->obligation)->all();

    expect($obligations)->not->toContain(ReportingObligation::CertLandNotification)
        ->not->toContain(ReportingObligation::KommunalaufsichtNotification);
});
