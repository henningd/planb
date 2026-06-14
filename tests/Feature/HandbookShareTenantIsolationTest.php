<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\HandbookShare;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;

/**
 * Erstellt eine Freigabe für eine Company unter Umgehung des Company-Scopes
 * (sonst würde das Anlegen im Test selbst vom Scope beeinflusst).
 */
function makeHandbookShareForCompany(Company $company): HandbookShare
{
    return HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'token' => bin2hex(random_bytes(24)),
        'label' => 'Test-Freigabe',
        'expires_at' => now()->addDays(14),
    ]);
}

function tenantOwnerWithCompany(): array
{
    $team = Team::factory()->create();
    $company = Company::factory()->for($team)->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $company];
}

test('a freshly registered user without a company profile sees no handbook shares', function () {
    // Bestehender Mandant mit einer Freigabe.
    [, $otherCompany] = tenantOwnerWithCompany();
    makeHandbookShareForCompany($otherCompany);

    // Neuer Nutzer: Team vorhanden, aber noch keine Company (vor Onboarding).
    $newUser = User::factory()->create();
    $newTeam = Team::factory()->create();
    $newTeam->members()->attach($newUser, ['role' => TeamRole::Owner->value]);
    $newUser->forceFill(['current_team_id' => $newTeam->id])->save();

    $this->actingAs($newUser->fresh());

    expect($newUser->fresh()->currentCompany())->toBeNull()
        ->and(HandbookShare::count())->toBe(0);
});

test('a user only sees handbook shares of their own company', function () {
    [$userA, $companyA] = tenantOwnerWithCompany();
    [, $companyB] = tenantOwnerWithCompany();

    makeHandbookShareForCompany($companyA);
    makeHandbookShareForCompany($companyB);
    makeHandbookShareForCompany($companyB);

    $this->actingAs($userA);

    expect(HandbookShare::count())->toBe(1);
});

test('console context without an authenticated user stays unscoped', function () {
    [, $company] = tenantOwnerWithCompany();
    makeHandbookShareForCompany($company);

    // Kein angemeldeter Nutzer -> Scope greift nicht (Jobs/Seeder/Konsole).
    expect(HandbookShare::count())->toBe(1);
});

test('a super admin without a company context is not denied', function () {
    [, $company] = tenantOwnerWithCompany();
    makeHandbookShareForCompany($company);

    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    // Kein current_team_id -> kein Company-Kontext, aber Super-Admin.
    $this->actingAs($superAdmin->fresh());

    expect($superAdmin->fresh()->currentCompany())->toBeNull()
        ->and(HandbookShare::count())->toBe(1);
});
