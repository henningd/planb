<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\EmergencyLevel;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('queries are automatically scoped to the authenticated user\'s company', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [$userB, $companyB] = makeUserWithCompany();

    Contact::factory()->for($companyA)->create(['name' => 'Alice']);
    Contact::factory()->for($companyB)->create(['name' => 'Bob']);

    $this->actingAs($userA);
    expect(Contact::pluck('name')->all())->toBe(['Alice']);

    $this->actingAs($userB);
    expect(Contact::pluck('name')->all())->toBe(['Bob']);
});

test('company_id is auto-filled on create from the authenticated user', function () {
    [$user, $company] = makeUserWithCompany();

    $this->actingAs($user);

    $contact = Contact::create([
        'name' => 'Erika Mustermann',
        'role' => 'Geschäftsführung',
    ]);

    expect($contact->company_id)->toBe($company->id);
});

test('emergency levels are scoped per company', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [$userB, $companyB] = makeUserWithCompany();

    $this->actingAs($userA);
    expect(EmergencyLevel::pluck('company_id')->unique()->all())->toBe([$companyA->id]);

    $this->actingAs($userB);
    expect(EmergencyLevel::pluck('company_id')->unique()->all())->toBe([$companyB->id]);
});

test('scope can be bypassed explicitly for admin or console contexts', function () {
    [$userA, $companyA] = makeUserWithCompany();
    [, $companyB] = makeUserWithCompany();

    Contact::factory()->for($companyA)->create();
    Contact::factory()->for($companyB)->create();

    $this->actingAs($userA);

    expect(Contact::count())->toBe(1)
        ->and(Contact::withoutGlobalScope(CurrentCompanyScope::class)->count())->toBe(2);
});

/**
 * Helper: creates a user whose personal team has a Company profile attached.
 *
 * @return array{0: User, 1: Company}
 */
function makeUserWithCompany(): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    return [$user->fresh(), $company];
}
