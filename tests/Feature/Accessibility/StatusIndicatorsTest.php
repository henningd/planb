<?php

use App\Enums\CrisisRole;
use App\Enums\HandbookTestType;
use App\Models\Company;
use App\Models\EmergencyLevel;
use App\Models\Employee;
use App\Models\HandbookTest;
use App\Models\System;
use App\Models\User;
use App\Support\Accessibility\SeverityIndicator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Setup-Helfer: User mit Company anlegen und einloggen.
 */
function accessibilityUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

test('SeverityIndicator liefert konsistente Heroicons je Schweregrad', function () {
    expect(SeverityIndicator::dashboardSeverityIcon('overdue'))->toBe('exclamation-triangle')
        ->and(SeverityIndicator::dashboardSeverityIcon('today'))->toBe('clock')
        ->and(SeverityIndicator::dashboardSeverityIcon('soon'))->toBe('calendar-days')
        ->and(SeverityIndicator::dashboardSeverityIcon('active'))->toBe('bell-alert');

    expect(SeverityIndicator::emergencyLevelIcon(1))->toBe('shield-exclamation')
        ->and(SeverityIndicator::emergencyLevelIcon(2))->toBe('exclamation-triangle')
        ->and(SeverityIndicator::emergencyLevelIcon(3))->toBe('shield-check');

    expect(SeverityIndicator::auditActionIcon('created'))->toBe('plus-circle')
        ->and(SeverityIndicator::auditActionIcon('deleted'))->toBe('trash')
        ->and(SeverityIndicator::auditActionIcon('updated'))->toBe('pencil-square')
        ->and(SeverityIndicator::auditActionIcon('employees.assigned'))->toBe('link')
        ->and(SeverityIndicator::auditActionIcon('employees.unassigned'))->toBe('link-slash');
});

test('Dashboard zeigt für überfällige Aktionen ein Warn-Icon zusätzlich zum Text', function () {
    $user = accessibilityUser();

    HandbookTest::factory()->for($user->currentCompany())->create([
        'name' => 'BCM-Test',
        'type' => HandbookTestType::Communication,
        'next_due_at' => now()->subDays(3)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Überfällig')
        ->assertSee('exclamation-triangle', false);
});

test('Notfall-Level-Liste zeigt Stufen-Icon zusätzlich zum Sort-Code', function () {
    $user = accessibilityUser();

    EmergencyLevel::factory()->for($user->currentCompany())->create([
        'name' => 'Kritisch',
        'sort' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('emergency-levels.index'));

    $response->assertOk()
        ->assertSee('Kritisch')
        ->assertSee('shield-exclamation', false);
});

test('System-Liste zeigt Notfall-Level-Badge mit Icon zusätzlich zur Farbe', function () {
    $user = accessibilityUser();
    $company = $user->currentCompany();

    $level = EmergencyLevel::factory()->for($company)->create([
        'name' => 'Kritisch',
        'sort' => 1,
    ]);

    System::factory()->for($company)->create([
        'name' => 'Kern-ERP',
        'emergency_level_id' => $level->id,
    ]);

    $response = $this->actingAs($user)->get(route('systems.index'));

    $response->assertOk()
        ->assertSee('Kritisch')
        ->assertSee('shield-exclamation', false);
});

test('Audit-Log-Liste zeigt Aktions-Badge mit Icon zusätzlich zur Farbe', function () {
    $user = accessibilityUser();

    $this->actingAs($user);
    System::create([
        'name' => 'Geprüftes System',
        'category' => 'basisbetrieb',
    ]);

    $response = $this->get(route('audit-log.index'));

    $response->assertOk()
        ->assertSee('Angelegt')
        ->assertSee('plus-circle', false);
});

test('Krisenrollen-Badge zeigt Schild-Icon zusätzlich zur roten Farbe', function () {
    $user = accessibilityUser();

    Employee::factory()
        ->for($user->currentCompany())
        ->withCrisisRole(CrisisRole::Management)
        ->create([
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
        ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Erika Mustermann')
        ->assertSee('shield-exclamation', false);
});
