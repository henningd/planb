<?php

use App\Enums\TeamRole;
use App\Models\AuthActivity;
use App\Models\Company;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Legt einen Owner-User mit aktiver Firma an und gibt das Tupel
 * [User, Company] zurück. Owner = Admin im Team-Block.
 *
 * @return array{0: User, 1: Company}
 */
function authExportAdminWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function authExportSeedEntry(string $companyId, ?int $userId, array $overrides = []): AuthActivity
{
    $createdAt = $overrides['created_at'] ?? now();
    unset($overrides['created_at']);

    $entry = AuthActivity::withoutGlobalScope(CurrentCompanyScope::class)->create(array_merge([
        'company_id' => $companyId,
        'user_id' => $userId,
        'email' => 'test@example.com',
        'event' => 'login',
        'ip_address' => '192.168.0.1',
        'user_agent' => 'PestBrowser/1.0',
    ], $overrides));

    // created_at ist nicht fillable — explizit über forceFill nachziehen.
    $entry->forceFill(['created_at' => $createdAt])->save();

    return $entry;
}

test('admin can download CSV with correct headers and entry rows', function () {
    [$user, $company] = authExportAdminWithCompany();

    authExportSeedEntry($company->id, $user->id, [
        'email' => 'csv-test@example.com',
        'event' => 'login',
        'created_at' => now()->addMinute(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('login-activity.export.csv'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $body = $response->streamedContent();
    expect($body)->toStartWith("\xEF\xBB\xBF");

    $lines = preg_split('/\r\n|\n/', trim($body));
    expect($lines[0])->toContain('Zeitpunkt;Benutzer;Ereignis;E-Mail;IP;User-Agent');

    expect($lines[1])->toContain('csv-test@example.com')
        ->and($lines[1])->toContain('Angemeldet')
        ->and($lines[1])->toContain($user->name)
        ->and($lines[1])->toContain('192.168.0.1');
});

test('admin can download PDF', function () {
    [$user, $company] = authExportAdminWithCompany();

    authExportSeedEntry($company->id, $user->id, ['email' => 'pdf-test@example.com']);

    $response = $this->actingAs($user)
        ->get(route('login-activity.export.pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('member without admin role gets 403 on both export routes', function () {
    [$owner, $company] = authExportAdminWithCompany();

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);
    $member = $member->fresh();

    authExportSeedEntry($company->id, $owner->id);

    $this->actingAs($member)
        ->get(route('login-activity.export.csv'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('login-activity.export.pdf'))
        ->assertForbidden();
});

test('CSV export is scoped to current company', function () {
    [$userA, $companyA] = authExportAdminWithCompany();

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();

    authExportSeedEntry($companyA->id, $userA->id, ['email' => 'firma-a@example.com']);
    authExportSeedEntry($companyB->id, $userB->id, ['email' => 'firma-b@example.com']);

    $body = $this->actingAs($userA)
        ->get(route('login-activity.export.csv', ['current_team' => $userA->currentTeam->slug]))
        ->streamedContent();

    expect($body)->toContain('firma-a@example.com')
        ->and($body)->not->toContain('firma-b@example.com');
});

test('CSV export respects date range filter', function () {
    [$user, $company] = authExportAdminWithCompany();

    authExportSeedEntry($company->id, $user->id, [
        'email' => 'alter-eintrag@example.com',
        'created_at' => now()->subDays(30),
    ]);
    authExportSeedEntry($company->id, $user->id, [
        'email' => 'neuer-eintrag@example.com',
        'created_at' => now()->subDay(),
    ]);

    $from = now()->subDays(7)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $body = $this->actingAs($user)
        ->get(route('login-activity.export.csv', ['from' => $from, 'to' => $to]))
        ->streamedContent();

    expect($body)->toContain('neuer-eintrag@example.com')
        ->and($body)->not->toContain('alter-eintrag@example.com');
});

test('CSV export respects event filter', function () {
    [$user, $company] = authExportAdminWithCompany();

    authExportSeedEntry($company->id, $user->id, [
        'email' => 'login-event@example.com',
        'event' => 'login',
    ]);
    authExportSeedEntry($company->id, $user->id, [
        'email' => 'failed-event@example.com',
        'event' => 'failed',
    ]);

    $body = $this->actingAs($user)
        ->get(route('login-activity.export.csv', ['event' => 'failed']))
        ->streamedContent();

    expect($body)->toContain('failed-event@example.com')
        ->and($body)->not->toContain('login-event@example.com');
});
