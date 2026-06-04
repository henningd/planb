<?php

use App\Enums\CrisisRole;
use App\Enums\ServiceProviderType;
use App\Enums\SystemCategory;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Legt einen User mit aktiver Firma an.
 *
 * @return array{0: User, 1: Company}
 */
function emergencyCardUserWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}

/**
 * Befüllt eine Firma mit etwas Notfallkarten-Inhalt: System, Krisenrolle
 * mit Mitarbeiter und einem Dienstleister mit Hotline.
 */
function emergencyCardSeedContent(Company $company): void
{
    System::factory()->for($company)->create([
        'name' => 'ERP',
        'category' => SystemCategory::Basisbetrieb,
    ]);

    Employee::factory()
        ->for($company)
        ->withCrisisRole(CrisisRole::EmergencyOfficer)
        ->create([
            'first_name' => 'Anna',
            'last_name' => 'Notfall',
            'position' => 'Leiterin',
            'mobile_phone' => '+49 170 1234567',
        ]);

    ServiceProvider::factory()->for($company)->create([
        'name' => 'ACME IT-Service',
        'type' => ServiceProviderType::ItMsp,
        'hotline' => '+49 800 1112222',
    ]);
}

test('member can download the emergency card PDF', function () {
    [$user, $company] = emergencyCardUserWithCompany();
    emergencyCardSeedContent($company);

    $response = $this->actingAs($user)
        ->get(route('emergency-card.pdf', ['current_team' => $user->currentTeam]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('emergency card filename contains the team slug', function () {
    [$user] = emergencyCardUserWithCompany();

    $response = $this->actingAs($user)
        ->get(route('emergency-card.pdf', ['current_team' => $user->currentTeam]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))
        ->toContain('notfallkarte-'.$user->currentTeam->slug.'.pdf');
});

test('emergency card works without any content', function () {
    [$user] = emergencyCardUserWithCompany();

    $response = $this->actingAs($user)
        ->get(route('emergency-card.pdf', ['current_team' => $user->currentTeam]));

    $response->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('emergency card is scoped to the acting user current company', function () {
    [$userA] = emergencyCardUserWithCompany();

    $userB = User::factory()->create();
    Company::factory()->for($userB->currentTeam)->create();

    $this->actingAs($userA->fresh())
        ->get(route('emergency-card.pdf', ['current_team' => $userA->currentTeam]))
        ->assertOk();

    $this->actingAs($userB->fresh())
        ->get(route('emergency-card.pdf', ['current_team' => $userB->currentTeam]))
        ->assertOk();
});
