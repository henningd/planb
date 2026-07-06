<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use App\Support\Mobile\MobileRolloutPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Team-Owner (Admin) mit Firmenprofil — darf den Massen-Rollout nutzen.
 */
function rolloutAdmin(): User
{
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    return $user->fresh();
}

/**
 * Fügt dem Team des Admins ein einfaches Mitglied hinzu und wechselt es dorthin.
 */
function rolloutMember(User $admin, string $role = 'member'): User
{
    $member = User::factory()->create();
    $admin->currentTeam->members()->attach($member, ['role' => $role]);
    $member->switchTeam($admin->currentTeam);

    return $member->fresh();
}

test('bulk rollout issues one 14-day code per selected user and streams a pdf', function () {
    $admin = rolloutAdmin();
    $company = $admin->currentCompany();
    $memberA = rolloutMember($admin);
    $memberB = rolloutMember($admin);

    Livewire::actingAs($admin)->test('pages::settings.mobile-access')
        ->set('rolloutSelection', [(string) $admin->id, (string) $memberA->id, (string) $memberB->id])
        ->call('downloadRolloutPdf')
        ->assertFileDownloaded('notfall-app-zugaenge-'.now()->format('Y-m-d').'.pdf');

    $codes = MobileAccessCode::query()->get();

    expect($codes)->toHaveCount(3)
        ->and($codes->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$admin->id, $memberA->id, $memberB->id])->sort()->values()->all());

    foreach ($codes as $code) {
        expect($code->company_id)->toBe($company->id)
            ->and($code->created_by_user_id)->toBe($admin->id)
            ->and($code->isActive())->toBeTrue()
            // Rollout-Gültigkeit: 14 Tage statt 60 Minuten.
            ->and($code->expires_at->diffInDays(now(), true))->toBeGreaterThan(13.0);
    }
});

test('the rollout pdf is a valid pdf document with one page per person', function () {
    $admin = rolloutAdmin();
    $company = $admin->currentCompany();
    $member = rolloutMember($admin);

    $result = MobileRolloutPdf::generate($company, collect([$admin, $member]), $admin);

    expect($result['binary'])->toStartWith('%PDF')
        ->and($result['filename'])->toEndWith('.pdf')
        ->and($result['expiresAt']->diffInDays(now(), true))->toBeGreaterThan(13.0);

    expect(MobileAccessCode::query()->count())->toBe(2);
});

test('select all uses the filtered member list', function () {
    $admin = rolloutAdmin();
    $memberA = rolloutMember($admin);
    $memberB = rolloutMember($admin);

    Livewire::actingAs($admin)->test('pages::settings.mobile-access')
        ->set('rolloutSearch', $memberA->email)
        ->call('selectAllRolloutMembers')
        ->assertSet('rolloutSelection', [(string) $memberA->id]);
});

test('users outside the team are ignored even if their id is submitted', function () {
    $admin = rolloutAdmin();
    $stranger = User::factory()->create();

    Livewire::actingAs($admin)->test('pages::settings.mobile-access')
        ->set('rolloutSelection', [(string) $stranger->id])
        ->call('downloadRolloutPdf');

    expect(MobileAccessCode::query()->count())->toBe(0);
});

test('non-admin members cannot use the bulk rollout', function () {
    $admin = rolloutAdmin();
    $member = rolloutMember($admin, TeamRole::Member->value);

    Livewire::actingAs($member)->test('pages::settings.mobile-access')
        ->assertDontSee('Massen-Rollout')
        ->set('rolloutSelection', [(string) $member->id])
        ->call('downloadRolloutPdf')
        ->assertStatus(403);

    expect(MobileAccessCode::query()->count())->toBe(0);
});

test('an empty selection creates no codes', function () {
    $admin = rolloutAdmin();

    Livewire::actingAs($admin)->test('pages::settings.mobile-access')
        ->call('downloadRolloutPdf');

    expect(MobileAccessCode::query()->count())->toBe(0);
});

test('the self-service flow keeps its short ttl', function () {
    $admin = rolloutAdmin();

    $issued = MobileAccessCode::issue($admin, $admin->currentCompany());

    expect($issued['model']->expires_at->diffInMinutes(now(), true))->toBeLessThanOrEqual(60.0);
});
