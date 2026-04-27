<?php

use App\Enums\CrisisRole;
use App\Enums\ReportingObligation;
use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Models\IncidentReport;
use App\Models\IncidentReportObligation;
use App\Models\ScenarioRun;
use App\Models\System;
use App\Models\User;
use App\Support\Incident\Cockpit;
use App\Support\Settings\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('isEnabledFor respects setting and feature flag', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    config(['features.incident_mode' => true]);
    expect(Cockpit::isEnabledFor($company))->toBeTrue();

    CompanySetting::for($company)->set('incident_mode_enabled', false);
    Cache::flush();
    expect(Cockpit::isEnabledFor($company))->toBeFalse();

    CompanySetting::for($company)->set('incident_mode_enabled', true);
    Cache::flush();
    expect(Cockpit::isEnabledFor($company))->toBeTrue();

    config(['features.incident_mode' => false]);
    expect(Cockpit::isEnabledFor($company))->toBeFalse();
});

test('crisisStaff returns mains and deputies separated', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::ItLead, deputy: false)
        ->create(['first_name' => 'Main', 'last_name' => 'IT']);

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::ItLead, deputy: true)
        ->create(['first_name' => 'Deputy', 'last_name' => 'One']);

    Employee::factory()->for($company)
        ->withCrisisRole(CrisisRole::ItLead, deputy: true)
        ->create(['first_name' => 'Deputy', 'last_name' => 'Two']);

    $cockpit = Cockpit::for($company);

    $itEntry = collect($cockpit->crisisStaff)->firstWhere('role', CrisisRole::ItLead);
    expect($itEntry)->not->toBeNull()
        ->and($itEntry['role_label'])->toBe(CrisisRole::ItLead->label())
        ->and($itEntry['main']?->first_name)->toBe('Main')
        ->and($itEntry['deputies'])->toHaveCount(2)
        ->and($itEntry['deputies']->pluck('last_name')->all())->toEqual(['One', 'Two']);

    $emptyEntry = collect($cockpit->crisisStaff)->firstWhere('role', CrisisRole::Management);
    expect($emptyEntry['main'])->toBeNull()
        ->and($emptyEntry['deputies'])->toHaveCount(0);

    expect($cockpit->crisisStaff)->toHaveCount(count(CrisisRole::cases()));
});

test('recoveryOrder sorts by emergency level then dependency depth', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $critical = EmergencyLevel::factory()->for($company)->create(['name' => 'Kritisch', 'sort' => 1]);
    $important = EmergencyLevel::factory()->for($company)->create(['name' => 'Wichtig', 'sort' => 2]);

    // Critical level: A and B, with B depending on A (so A has a dependent => higher depth)
    $a = System::factory()->for($company)->create(['name' => 'A-Database', 'emergency_level_id' => $critical->id]);
    $b = System::factory()->for($company)->create(['name' => 'B-App', 'emergency_level_id' => $critical->id]);
    $b->dependencies()->attach($a->id);

    // Important level: standalone
    $c = System::factory()->for($company)->create(['name' => 'C-Reporting', 'emergency_level_id' => $important->id]);

    // No level
    $d = System::factory()->for($company)->create(['name' => 'D-Misc', 'emergency_level_id' => null]);

    $cockpit = Cockpit::for($company);
    $order = $cockpit->recoveryOrder;

    expect($order)->toHaveCount(4);

    $names = array_map(fn ($item) => $item['system']->name, $order);

    // A (depth 1) comes before B (depth 0) in the same level
    expect($names[0])->toBe('A-Database');
    expect($names[1])->toBe('B-App');
    // Important next, then no-level last
    expect($names[2])->toBe('C-Reporting');
    expect($names[3])->toBe('D-Misc');

    expect($order[0]['depth'])->toBe(1)
        ->and($order[1]['depth'])->toBe(0)
        ->and($order[0]['level_name'])->toBe('Kritisch')
        ->and($order[3]['level_sort'])->toBeNull();
});

test('obligations are empty when no active run', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    // Create a closed run + an incident report on it — obligations should
    // still be empty because there is no *active* run.
    $closedRun = ScenarioRun::factory()->for($company)->create([
        'started_at' => now()->subHour(),
        'ended_at' => now()->subMinutes(10),
    ]);

    $report = IncidentReport::factory()->for($company)->create([
        'scenario_run_id' => $closedRun->id,
        'occurred_at' => now()->subMinutes(30),
    ]);

    IncidentReportObligation::create([
        'incident_report_id' => $report->id,
        'obligation' => ReportingObligation::DsgvoNotification,
    ]);

    $cockpit = Cockpit::for($company);

    expect($cockpit->hasActiveRun())->toBeFalse()
        ->and($cockpit->obligations)->toBe([]);
});

test('no active run leads to hasActiveRun false', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $cockpit = Cockpit::for($company);

    expect($cockpit->hasActiveRun())->toBeFalse()
        ->and($cockpit->activeRun)->toBeNull()
        ->and($cockpit->steps)->toHaveCount(0)
        ->and($cockpit->recoveryOrder)->toBe([])
        ->and($cockpit->obligations)->toBe([]);
});
