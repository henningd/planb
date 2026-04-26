<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\HandbookVersion;
use App\Models\HandbookVersionAcknowledgement;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can acknowledge a version via the ui', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $employee = Employee::factory()->for($company)->create(['is_key_personnel' => true]);
    $version = HandbookVersion::factory()->for($company)->approved()->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->call('openAcks', $version->id)
        ->set('ackEmployeeId', $employee->id)
        ->set('ackNotes', 'Per Teams bestätigt')
        ->call('acknowledge')
        ->assertHasNoErrors();

    expect(HandbookVersionAcknowledgement::count())->toBe(1);
    $ack = HandbookVersionAcknowledgement::first();
    expect($ack->employee_id)->toBe($employee->id);
    expect($ack->notes)->toBe('Per Teams bestätigt');
});

test('acknowledgement rate reports 0, partial and 100 percent', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $key1 = Employee::factory()->for($company)->create(['is_key_personnel' => true]);
    $key2 = Employee::factory()->for($company)->create(['is_key_personnel' => true]);
    Employee::factory()->for($company)->create(['is_key_personnel' => false]);

    $version = HandbookVersion::factory()->for($company)->approved()->create();

    expect($version->acknowledgementRate())->toBe(0.0);

    HandbookVersionAcknowledgement::create([
        'handbook_version_id' => $version->id,
        'employee_id' => $key1->id,
        'acknowledged_at' => now(),
    ]);
    expect(round($version->fresh()->acknowledgementRate(), 2))->toBe(0.5);

    HandbookVersionAcknowledgement::create([
        'handbook_version_id' => $version->id,
        'employee_id' => $key2->id,
        'acknowledged_at' => now(),
    ]);
    expect($version->fresh()->acknowledgementRate())->toBe(1.0);
});

test('acknowledgement rate falls back to all employees when no key personnel exists', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    Employee::factory()->for($company)->count(2)->create(['is_key_personnel' => false]);

    $version = HandbookVersion::factory()->for($company)->approved()->create();

    expect($version->acknowledgementRate())->toBe(0.0);
});

test('a duplicate acknowledgement is rejected', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $employee = Employee::factory()->for($company)->create(['is_key_personnel' => true]);
    $version = HandbookVersion::factory()->for($company)->approved()->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->call('openAcks', $version->id)
        ->set('ackEmployeeId', $employee->id)
        ->call('acknowledge')
        ->set('ackEmployeeId', $employee->id)
        ->call('acknowledge');

    expect(HandbookVersionAcknowledgement::count())->toBe(1);
});

test('an employee from another company cannot acknowledge', function () {
    $owner = User::factory()->create();
    $companyA = Company::factory()->for($owner->currentTeam)->create();
    $version = HandbookVersion::factory()->for($companyA)->approved()->create();

    $companyB = Company::factory()->for(Team::factory())->create();
    $foreignEmployee = Employee::factory()->for($companyB)->create();

    Livewire\Livewire::actingAs($owner->fresh())
        ->test('pages::handbook-versions.index')
        ->call('openAcks', $version->id)
        ->set('ackEmployeeId', $foreignEmployee->id)
        ->call('acknowledge')
        ->assertHasNoErrors();

    expect(HandbookVersionAcknowledgement::count())->toBe(0);
});

test('deleting an employee cascades and removes their acknowledgement', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $employee = Employee::factory()->for($company)->create(['is_key_personnel' => true]);
    $version = HandbookVersion::factory()->for($company)->approved()->create();

    HandbookVersionAcknowledgement::create([
        'handbook_version_id' => $version->id,
        'employee_id' => $employee->id,
        'acknowledged_at' => now(),
    ]);

    expect(HandbookVersionAcknowledgement::count())->toBe(1);

    $employee->delete();

    expect(HandbookVersionAcknowledgement::count())->toBe(0);
});
