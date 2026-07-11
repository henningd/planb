<?php

use App\Enums\AuthorityContactType;
use App\Enums\Industry;
use App\Models\AuthorityContact;
use App\Models\Company;
use App\Models\User;
use App\Support\Compliance\Catalog;
use App\Support\HandbookData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Company}
 */
function authorityActingUser(?Industry $industry = null): array
{
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'industry' => ($industry ?? Industry::Dienstleistung)->value,
    ]);

    return [$user->fresh(), $company];
}

test('the register lists authority contacts of the current company', function () {
    [$user, $company] = authorityActingUser();
    AuthorityContact::factory()->create([
        'company_id' => $company->id,
        'name' => 'Landesdatenschutzbehörde Bayern',
        'type' => AuthorityContactType::DataProtection,
    ]);

    $this->actingAs($user)
        ->get(route('authority-contacts.index', $user->currentTeam))
        ->assertOk()
        ->assertSee('Landesdatenschutzbehörde Bayern');
});

test('a contact can be created with type, deadline and role', function () {
    [$user, $company] = authorityActingUser();
    $role = $company->roles()->first();

    Livewire::actingAs($user)
        ->test('pages::authority-contacts.index')
        ->set('type', AuthorityContactType::Bsi->value)
        ->set('name', 'BSI-Meldestelle')
        ->set('occasion', 'Erheblicher Sicherheitsvorfall (NIS2)')
        ->set('deadline', 'binnen 24 Stunden')
        ->set('phone', '0228 999582-0')
        ->set('responsible_role_id', (string) ($role?->id ?? ''))
        ->call('save')
        ->assertHasNoErrors();

    $contact = AuthorityContact::firstWhere('name', 'BSI-Meldestelle');
    expect($contact)->not->toBeNull()
        ->and($contact->company_id)->toBe($company->id)
        ->and($contact->type)->toBe(AuthorityContactType::Bsi)
        ->and($contact->deadline)->toBe('binnen 24 Stunden');
});

test('company creation seeds branch-specific authority contacts', function () {
    [, $company] = authorityActingUser(Industry::Produktion);

    $names = $company->authorityContacts()->pluck('name');

    // Allgemeine Basis für jede Firma …
    expect($names)->toContain('Zuständige Datenschutzaufsichtsbehörde')
        ->toContain('Polizei / Zentrale Ansprechstelle Cybercrime (ZAC)');

    // … plus Produktions-spezifische Ergänzungen.
    expect($names)->toContain('Umweltbehörde');
});

test('authority contacts are scoped to the current company', function () {
    [$user] = authorityActingUser();

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    AuthorityContact::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Fremde Behörde XYZ',
    ]);

    $this->actingAs($user)
        ->get(route('authority-contacts.index', $user->currentTeam))
        ->assertOk()
        ->assertDontSee('Fremde Behörde XYZ');
});

test('the detail page shows a contact and blocks foreign tenants', function () {
    [$user, $company] = authorityActingUser();
    $contact = AuthorityContact::factory()->create([
        'company_id' => $company->id,
        'name' => 'Kreisleitstelle Musterkreis',
        'contact_way' => 'https://meldeportal.example/behoerde',
    ]);

    $this->actingAs($user)
        ->get(route('authority-contacts.show', $contact))
        ->assertOk()
        ->assertSee('Kreisleitstelle Musterkreis')
        ->assertSee('https://meldeportal.example/behoerde');

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create();
    $foreign = AuthorityContact::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($user)
        ->get(route('authority-contacts.show', $foreign))
        ->assertNotFound();
});

test('the compliance catalog has an authority contacts check', function () {
    [$user, $company] = authorityActingUser();
    $this->actingAs($user);

    $check = collect(Catalog::all())->firstWhere('key', 'authority.contacts');
    expect($check)->not->toBeNull();

    // Kontakt ohne jeden Kontaktweg → Teil-Erfüllung (< 100).
    AuthorityContact::factory()->create([
        'company_id' => $company->id,
        'phone' => null, 'email' => null, 'contact_way' => null,
    ]);
    expect($check->evaluate($company->fresh())->score)->toBeLessThan(100);
});

test('the handbook data includes the authority contacts', function () {
    [, $company] = authorityActingUser();
    AuthorityContact::factory()->create([
        'company_id' => $company->id,
        'name' => 'Gewerbeaufsicht Musterstadt',
    ]);

    $data = HandbookData::forCompany($company->fresh());

    expect($data)->toHaveKey('authorityContacts')
        ->and($data['authorityContacts']->pluck('name'))->toContain('Gewerbeaufsicht Musterstadt');
});
