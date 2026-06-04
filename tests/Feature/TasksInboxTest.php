<?php

use App\Models\Company;
use App\Models\EmergencyResource;
use App\Models\HandbookTest;
use App\Models\System;
use App\Models\SystemTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bootInboxUser(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();
    test()->actingAs($user->fresh());

    return $user->fresh();
}

test('inbox lists due resources and tests but not system tasks', function () {
    $user = bootInboxUser();

    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Offline-Backup', 'next_check_at' => now()->addDays(3)->toDateString()]);
    HandbookTest::create(['type' => 'backup_restore', 'interval' => 'monthly', 'name' => 'Restore-Test', 'next_due_at' => now()->addDays(5)->toDateString()]);

    $system = System::create(['name' => 'ERP', 'category' => 'geschaeftsbetrieb']);
    SystemTask::create(['system_id' => $system->id, 'title' => 'System-Aufgabe']);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSeeText('Offline-Backup')
        ->assertSeeText('Restore-Test')
        ->assertDontSeeText('System-Aufgabe');
});

test('inbox items deep-link to the specific entry', function () {
    $user = bootInboxUser();
    $resource = EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Backup', 'next_check_at' => now()->addDays(3)->toDateString()]);
    $test = HandbookTest::create(['type' => 'backup_restore', 'interval' => 'monthly', 'name' => 'Restore', 'next_due_at' => now()->addDays(3)->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSee('#resource-'.$resource->id)
        ->assertSee('#test-'.$test->id);
});

test('inbox is scoped to the current company', function () {
    $user = bootInboxUser();
    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Mein Sofortmittel', 'next_check_at' => now()->addDays(2)->toDateString()]);

    $other = User::factory()->create();
    Company::factory()->for($other->currentTeam)->create();
    test()->actingAs($other->fresh());
    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Fremdes Sofortmittel', 'next_check_at' => now()->addDays(2)->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSeeText('Mein Sofortmittel')
        ->assertDontSeeText('Fremdes Sofortmittel');
});

test('overdue filter shows only overdue items', function () {
    $user = bootInboxUser();

    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Überfällige Prüfung', 'next_check_at' => now()->subDays(2)->toDateString()]);
    HandbookTest::create(['type' => 'tabletop', 'interval' => 'yearly', 'name' => 'Künftiger Test', 'next_due_at' => now()->addDays(20)->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->set('statusFilter', 'overdue')
        ->assertSeeText('Überfällige Prüfung')
        ->assertDontSeeText('Künftiger Test');
});

test('type filter narrows to a single source', function () {
    $user = bootInboxUser();

    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Nur Sofortmittel', 'next_check_at' => now()->addDays(2)->toDateString()]);
    HandbookTest::create(['type' => 'tabletop', 'interval' => 'yearly', 'name' => 'Nur Testplan', 'next_due_at' => now()->addDays(2)->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->set('typeFilter', 'resources')
        ->assertSeeText('Nur Sofortmittel')
        ->assertDontSeeText('Nur Testplan')
        ->set('typeFilter', 'tests')
        ->assertSeeText('Nur Testplan')
        ->assertDontSeeText('Nur Sofortmittel');
});

test('marking a resource checked sets last check today and clears next', function () {
    $user = bootInboxUser();
    $resource = EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Backup', 'next_check_at' => now()->subDay()->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->call('markResourceChecked', $resource->id);

    $fresh = $resource->fresh();
    expect($fresh->last_check_at->isToday())->toBeTrue()
        ->and($fresh->next_check_at)->toBeNull();
});

test('marking a test executed advances the next due date', function () {
    $user = bootInboxUser();
    $test = HandbookTest::create(['type' => 'backup_restore', 'interval' => 'monthly', 'name' => 'Restore', 'next_due_at' => now()->subDay()->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->call('markTestExecuted', $test->id);

    $fresh = $test->fresh();
    expect($fresh->last_executed_at->isToday())->toBeTrue()
        ->and($fresh->next_due_at->toDateString())->toBe(now()->addMonth()->toDateString());
});

test('counters reflect open, overdue and done today', function () {
    $user = bootInboxUser();

    EmergencyResource::create(['type' => 'offline_backup', 'name' => 'Offen', 'next_check_at' => now()->addDays(5)->toDateString()]);
    EmergencyResource::create(['type' => 'offline_docs', 'name' => 'Überfällig', 'next_check_at' => now()->subDays(2)->toDateString()]);
    HandbookTest::create(['type' => 'tabletop', 'interval' => 'yearly', 'name' => 'Test überfällig', 'next_due_at' => now()->subDay()->toDateString()]);
    EmergencyResource::create(['type' => 'password_safe', 'name' => 'Heute geprüft', 'last_check_at' => now()->toDateString()]);

    Livewire\Livewire::actingAs($user)
        ->test('pages::tasks-inbox.index')
        ->assertSet('openCount', 3)
        ->assertSet('overdueCount', 2)
        ->assertSet('doneTodayCount', 1);
});
