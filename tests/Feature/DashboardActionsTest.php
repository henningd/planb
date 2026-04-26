<?php

use App\Enums\HandbookTestType;
use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\HandbookVersion;
use App\Models\IncidentReport;
use App\Models\ScenarioRun;
use App\Models\Team;
use App\Models\User;
use App\Support\DashboardActions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns empty array when nothing is due', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    expect(DashboardActions::for($company))->toBe([]);
});

test('collects items from all relevant sources', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookTest::factory()->for($company)->create([
        'name' => 'SMS-Notfallkette',
        'type' => HandbookTestType::Communication,
        'next_due_at' => now()->addDays(3)->toDateString(),
    ]);

    EmergencyResource::factory()->for($company)->create([
        'name' => 'Notfallkasse',
        'next_check_at' => now()->addDays(5)->toDateString(),
    ]);

    ScenarioRun::factory()->for($company)->create([
        'title' => 'Brand Serverraum',
        'started_at' => now()->subHour(),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    $report = IncidentReport::factory()->for($company)->create([
        'title' => 'Ransomware Buchhaltung',
        'type' => IncidentType::CyberAttack,
    ]);
    $report->obligations()->create([
        'obligation' => ReportingObligation::DsgvoNotification->value,
        'reported_at' => null,
    ]);

    HandbookVersion::factory()->for($company)->create([
        'version' => '1.2',
        'approved_at' => null,
    ]);

    $items = DashboardActions::for($company);

    $types = collect($items)->pluck('type')->all();

    expect($types)->toContain('test')
        ->and($types)->toContain('resource')
        ->and($types)->toContain('scenario_run')
        ->and($types)->toContain('incident_obligation')
        ->and($types)->toContain('handbook_version');
});

test('items are sorted by severity: overdue, today, soon, active', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // soon
    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(7)->toDateString(),
    ]);
    // today
    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->toDateString(),
    ]);
    // overdue
    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->subDays(2)->toDateString(),
    ]);
    // active
    ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subMinutes(30),
        'ended_at' => null,
        'aborted_at' => null,
    ]);

    $severities = collect(DashboardActions::for($company))->pluck('severity')->all();

    expect($severities)->toBe(['overdue', 'today', 'soon', 'active']);
});

test('returns only items of the given company (tenant isolation)', function () {
    $user = User::factory()->create();
    $own = Company::factory()->for($user->currentTeam)->create();
    $other = Company::factory()->for(Team::factory())->create();

    HandbookTest::factory()->for($own)->create([
        'name' => 'eigen',
        'next_due_at' => now()->addDay()->toDateString(),
    ]);
    HandbookTest::factory()->for($other)->create([
        'name' => 'fremd',
        'next_due_at' => now()->addDay()->toDateString(),
    ]);

    $labels = collect(DashboardActions::for($own))->pluck('label')->all();

    expect($labels)->toHaveCount(1)
        ->and($labels[0])->toContain('eigen')
        ->and($labels[0])->not->toContain('fremd');
});

test('items beyond the 14-day window are not included', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookTest::factory()->for($company)->create([
        'next_due_at' => now()->addDays(30)->toDateString(),
    ]);
    EmergencyResource::factory()->for($company)->create([
        'next_check_at' => now()->addDays(40)->toDateString(),
    ]);

    expect(DashboardActions::for($company))->toBe([]);
});

test('reported obligations are not listed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $report = IncidentReport::factory()->for($company)->create();
    $report->obligations()->create([
        'obligation' => ReportingObligation::DsgvoNotification->value,
        'reported_at' => now(),
    ]);

    expect(DashboardActions::for($company))->toBe([]);
});

test('approved handbook versions are not listed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    HandbookVersion::factory()->for($company)->approved()->create();

    expect(DashboardActions::for($company))->toBe([]);
});

test('ended or aborted scenario runs are not listed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subHours(3),
        'ended_at' => now()->subHour(),
    ]);
    ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subHours(3),
        'aborted_at' => now()->subHour(),
    ]);

    expect(DashboardActions::for($company))->toBe([]);
});
