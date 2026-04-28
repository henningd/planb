<?php

use App\Models\AccountDeletionRequest;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

test('guest is redirected to login when visiting the data privacy page', function () {
    $this->get(route('settings.data-privacy'))->assertRedirect(route('login'));
});

test('guest is redirected to login when triggering the export', function () {
    $this->get(route('settings.data-privacy.export'))->assertRedirect(route('login'));
});

test('authenticated user can view the data privacy page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.data-privacy'))
        ->assertOk()
        ->assertSee(__('Daten exportieren'))
        ->assertSee(__('Account-Löschung beantragen'));
});

test('export endpoint returns json with attachment header and the user payload', function () {
    $user = User::factory()->create([
        'name' => 'Datenexport Tester',
        'email' => 'export@example.test',
    ]);

    $team = $user->currentTeam;
    Company::factory()->create([
        'team_id' => $team->id,
        'name' => 'Acme GmbH',
    ]);
    $company = $team->company()->first();

    AuditLogEntry::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'entity_type' => 'System',
        'entity_id' => '00000000-0000-0000-0000-000000000001',
        'entity_label' => 'Mailserver',
        'action' => 'updated',
        'changes' => ['name' => ['old' => 'Old', 'new' => 'New']],
    ]);

    $other = User::factory()->create();
    AuditLogEntry::create([
        'company_id' => $company->id,
        'user_id' => $other->id,
        'entity_type' => 'System',
        'entity_id' => '00000000-0000-0000-0000-000000000002',
        'entity_label' => 'Other entry',
        'action' => 'created',
        'changes' => null,
    ]);

    $response = $this->actingAs($user)->get(route('settings.data-privacy.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/json');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('planb-account-'.$user->id.'-'.now()->format('Y-m-d').'.json');

    $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)
        ->toHaveKeys(['generated_at', 'user', 'memberships', 'audit_log_entries']);

    expect($payload['user'])
        ->toMatchArray([
            'id' => $user->id,
            'name' => 'Datenexport Tester',
            'email' => 'export@example.test',
        ])
        ->toHaveKeys(['created_at', 'updated_at', 'email_verified_at', 'two_factor_confirmed_at']);

    expect($payload['memberships'])->toHaveCount(1);
    expect($payload['memberships'][0])
        ->toMatchArray([
            'team_id' => $team->id,
            'company_name' => 'Acme GmbH',
            'role' => 'owner',
        ]);

    expect($payload['audit_log_entries'])->toHaveCount(1);
    expect($payload['audit_log_entries'][0])
        ->toMatchArray([
            'entity_type' => 'System',
            'entity_label' => 'Mailserver',
            'action' => 'updated',
        ]);
});

test('user can submit a deletion request which is stored as pending', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.data-privacy')
        ->set('reason', 'Bitte mein Konto löschen.')
        ->call('requestDeletion')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('account_deletion_requests', [
        'user_id' => $user->id,
        'status' => AccountDeletionRequest::STATUS_PENDING,
        'reason' => 'Bitte mein Konto löschen.',
    ]);

    expect(AccountDeletionRequest::where('user_id', $user->id)->count())->toBe(1);
});

test('user with an existing pending request cannot create a second one', function () {
    $user = User::factory()->create();

    AccountDeletionRequest::create([
        'user_id' => $user->id,
        'status' => AccountDeletionRequest::STATUS_PENDING,
        'requested_at' => now()->subDay(),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.data-privacy')
        ->set('reason', 'Zweiter Versuch.')
        ->call('requestDeletion');

    expect(AccountDeletionRequest::where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('settings.data-privacy'))
        ->assertSee(__('Löschanfrage in Bearbeitung'));
});
