<?php

use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Database\Seeders\GlobalScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(GlobalScenariosSeeder::class));

test('authenticated user can create and revoke a share link', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-shares.index')
        ->set('label', 'Auditor Musterprüfer')
        ->set('validDays', 7)
        ->call('create')
        ->assertHasNoErrors();

    $share = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->first();

    expect($share)->not->toBeNull()
        ->and($share->label)->toBe('Auditor Musterprüfer')
        ->and($share->expires_at->isFuture())->toBeTrue()
        ->and(strlen($share->token))->toBeGreaterThanOrEqual(32);

    $component->call('confirmRevoke', $share->id)
        ->call('revoke')
        ->assertHasNoErrors();

    expect($share->fresh()->revoked_at)->not->toBeNull();
});

test('public share link renders the handbook and counts accesses', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Musterfirma']);

    $share = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'token' => 'testtoken123',
        'label' => 'Versicherung',
        'expires_at' => Carbon::now()->addDays(7),
    ]);

    $this->get(route('handbook.shared', $share->token))
        ->assertOk()
        ->assertSee('Musterfirma')
        ->assertSee('Read-only-Zugriff ohne Login')
        ->assertSee('Versicherung');

    $share->refresh();
    expect($share->access_count)->toBe(1)
        ->and($share->last_accessed_at)->not->toBeNull();
});

test('expired share link returns a 410 inactive page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $share = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'token' => 'oldtoken',
        'label' => 'Alt',
        'expires_at' => Carbon::now()->subDay(),
    ]);

    $response = $this->get(route('handbook.shared', $share->token));

    $response->assertStatus(410)
        ->assertSee('abgelaufen');
});

test('revoked share link returns a 410 inactive page', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $share = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'token' => 'revokedtoken',
        'label' => 'Gesperrt',
        'expires_at' => Carbon::now()->addDays(7),
        'revoked_at' => Carbon::now(),
    ]);

    $response = $this->get(route('handbook.shared', $share->token));

    $response->assertStatus(410)
        ->assertSee('widerrufen');
});

test('unknown share token returns 404', function () {
    $this->get(route('handbook.shared', 'nosuchtoken'))->assertNotFound();
});

test('shares are scoped per company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $other = Company::factory()->for(Team::factory())->create();

    HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'token' => 'own',
        'label' => 'Eigen',
        'expires_at' => Carbon::now()->addDay(),
    ]);

    HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $other->id,
        'token' => 'foreign',
        'label' => 'Fremd',
        'expires_at' => Carbon::now()->addDay(),
    ]);

    $this->actingAs($user->fresh());

    expect(HandbookShare::pluck('label')->all())->toBe(['Eigen']);
});
