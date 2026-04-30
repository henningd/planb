<?php

use App\Enums\InsuranceType;
use App\Models\Company;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\Settings\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 without bearer token', function () {
    $this->getJson('/api/v1/portal/profile')
        ->assertStatus(401)
        ->assertJson(['error' => 'missing_token']);
});

it('returns 401 with an invalid bearer token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson('/api/v1/portal/profile')
        ->assertStatus(401)
        ->assertJson(['error' => 'invalid_token']);
});

it('returns 403 when the company has not opted in', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $token = bin2hex(random_bytes(32));
    $company->forceFill(['portal_api_token_hash' => hash('sha256', $token)])->save();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/portal/profile')
        ->assertStatus(403)
        ->assertJson(['error' => 'portal_link_disabled']);
});

it('returns the stub profile when opted in and token matches', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'name' => 'Demo GmbH',
    ]);

    InsurancePolicy::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'type' => InsuranceType::Cyber,
        'insurer' => 'Cyber AG',
    ]);

    CompanySetting::for($company)->set('portal_link_enabled', true);

    $token = bin2hex(random_bytes(32));
    $company->forceFill(['portal_api_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/portal/profile')
        ->assertOk()
        ->assertJson([
            'stub' => true,
            'company' => [
                'id' => $company->id,
                'name' => 'Demo GmbH',
            ],
            'insured_types' => ['cyber'],
        ])
        ->assertJsonStructure([
            'stub', 'note', 'company', 'crisis_roles', 'insured_types', 'tasks',
        ]);

    expect($company->fresh()->portal_link_last_used_at)->not->toBeNull();
});
