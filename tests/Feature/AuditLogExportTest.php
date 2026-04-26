<?php

use App\Enums\TeamRole;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Legt einen Owner-User mit aktiver Firma an und gibt das Tupel
 * [User, Company] zurück. Owner = Admin im Team-Block.
 *
 * @return array{0: User, 1: Company}
 */
function exportAdminWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

function exportSeedEntry(string $companyId, ?string $userId, array $overrides = []): AuditLogEntry
{
    return AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)->create(array_merge([
        'company_id' => $companyId,
        'user_id' => $userId,
        'entity_type' => 'System',
        'entity_id' => (string) Str::uuid(),
        'entity_label' => 'Beispiel-System',
        'action' => 'created',
        'changes' => ['name' => 'Beispiel-System'],
        'created_at' => now(),
    ], $overrides));
}

test('admin can download CSV with correct headers and entry rows', function () {
    [$user, $company] = exportAdminWithCompany();

    exportSeedEntry($company->id, $user->id, [
        'entity_label' => 'ERP-Test',
        'created_at' => now()->setTime(9, 30),
    ]);

    $response = $this->actingAs($user)
        ->get(route('audit-log.export.csv'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $body = $response->streamedContent();
    expect($body)->toStartWith("\xEF\xBB\xBF");

    $lines = preg_split('/\r\n|\n/', trim($body));
    // Header steht in Zeile 0 (BOM ist Teil dieser Zeile).
    expect($lines[0])->toContain('Datum;Benutzer;Aktion;Objekt-Typ;Objekt;');

    expect($lines[1])->toContain('ERP-Test')
        ->and($lines[1])->toContain('Angelegt')
        ->and($lines[1])->toContain('System')
        ->and($lines[1])->toContain($user->name);
});

test('admin can download PDF', function () {
    [$user, $company] = exportAdminWithCompany();

    exportSeedEntry($company->id, $user->id, ['entity_label' => 'PDF-System']);

    $response = $this->actingAs($user)
        ->get(route('audit-log.export.pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('member without admin role gets 403 on both export routes', function () {
    [$owner, $company] = exportAdminWithCompany();

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);
    $member = $member->fresh();

    exportSeedEntry($company->id, $owner->id);

    $this->actingAs($member)
        ->get(route('audit-log.export.csv'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('audit-log.export.pdf'))
        ->assertForbidden();
});

test('CSV export is scoped to current company', function () {
    [$userA, $companyA] = exportAdminWithCompany();

    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();

    exportSeedEntry($companyA->id, $userA->id, ['entity_label' => 'Firma-A-Eintrag']);
    exportSeedEntry($companyB->id, $userB->id, ['entity_label' => 'Firma-B-Eintrag']);

    $body = $this->actingAs($userA)
        ->get(route('audit-log.export.csv'))
        ->streamedContent();

    expect($body)->toContain('Firma-A-Eintrag')
        ->and($body)->not->toContain('Firma-B-Eintrag');
});

test('CSV export respects date range filter', function () {
    [$user, $company] = exportAdminWithCompany();

    exportSeedEntry($company->id, $user->id, [
        'entity_label' => 'Alter-Eintrag',
        'created_at' => now()->subDays(30),
    ]);
    exportSeedEntry($company->id, $user->id, [
        'entity_label' => 'Neuer-Eintrag',
        'created_at' => now()->subDay(),
    ]);

    $from = now()->subDays(7)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $body = $this->actingAs($user)
        ->get(route('audit-log.export.csv', ['from' => $from, 'to' => $to]))
        ->streamedContent();

    expect($body)->toContain('Neuer-Eintrag')
        ->and($body)->not->toContain('Alter-Eintrag');
});

test('CSV export respects entity_type filter', function () {
    [$user, $company] = exportAdminWithCompany();

    exportSeedEntry($company->id, $user->id, [
        'entity_type' => 'System',
        'entity_label' => 'System-X',
    ]);
    exportSeedEntry($company->id, $user->id, [
        'entity_type' => 'Employee',
        'entity_label' => 'Mitarbeiter-Y',
    ]);

    $body = $this->actingAs($user)
        ->get(route('audit-log.export.csv', ['entity_type' => 'Employee']))
        ->streamedContent();

    expect($body)->toContain('Mitarbeiter-Y')
        ->and($body)->not->toContain('System-X');
});
