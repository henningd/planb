<?php

use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\System;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a system logs an audit entry with entity label and user', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    System::create([
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    $entry = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->where('entity_type', 'System')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->entity_type)->toBe('System')
        ->and($entry->entity_label)->toBe('ERP')
        ->and($entry->action)->toBe('created')
        ->and($entry->user_id)->toBe($user->id);
});

test('updating a system records the dirty field diff', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $system = System::create([
        'name' => 'ERP',
        'category' => 'geschaeftsbetrieb',
    ]);

    $system->update(['name' => 'ERP v2']);

    $updateEntry = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('action', 'updated')
        ->first();

    expect($updateEntry)->not->toBeNull()
        ->and($updateEntry->changes)->toHaveKey('name')
        ->and($updateEntry->changes['name']['old'])->toBe('ERP')
        ->and($updateEntry->changes['name']['new'])->toBe('ERP v2');
});

test('deleting a system records a deleted entry', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    $system = System::create(['name' => 'ERP', 'category' => 'geschaeftsbetrieb']);
    $system->delete();

    $entry = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('action', 'deleted')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->entity_type)->toBe('System')
        ->and($entry->entity_label)->toBe('ERP');
});

test('audit entries are scoped to the current company', function () {
    $userA = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create();

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();

    $this->actingAs($userA->fresh());
    System::create(['name' => 'A-System', 'category' => 'basisbetrieb']);

    $this->actingAs($userB->fresh());
    System::create(['name' => 'B-System', 'category' => 'basisbetrieb']);

    $this->actingAs($userA->fresh());
    expect(AuditLogEntry::where('entity_type', 'System')->pluck('entity_label')->all())->toBe(['A-System']);
});

test('employee audit label uses full name', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());

    Employee::create([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
    ]);

    $entry = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('entity_type', 'Employee')
        ->first();

    expect($entry->entity_label)->toBe('Erika Mustermann');
});

test('audit log page renders with entries', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $this->actingAs($user->fresh());
    System::create(['name' => 'Sichtbares System', 'category' => 'basisbetrieb']);

    $this->get(route('audit-log.index'))
        ->assertOk()
        ->assertSee('Aktivitätsprotokoll')
        ->assertSee('Sichtbares System')
        ->assertSee('Angelegt');
});
