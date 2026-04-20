<?php

use App\Enums\Industry;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\IndustryTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('industry templates expose contact roles for every supported industry', function () {
    foreach ([Industry::Handwerk, Industry::Handel, Industry::Dienstleistung, Industry::Produktion] as $industry) {
        expect(IndustryTemplates::contactRolesFor($industry->value))->not->toBeEmpty();
    }
});

test('loading role template creates placeholder contacts with prefilled role and type', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['industry' => Industry::Handel->value]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::contacts.index')
        ->set('roleTemplateKey', Industry::Handel->value)
        ->call('loadRoleTemplate')
        ->assertHasNoErrors();

    $contacts = Contact::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->get();

    $expected = collect(IndustryTemplates::contactRolesFor(Industry::Handel->value))->pluck('role')->all();
    $actual = $contacts->pluck('role')->all();

    expect($actual)->toEqualCanonicalizing($expected)
        ->and($contacts->every(fn (Contact $c) => $c->name === '(zu benennen)'))->toBeTrue();
});

test('re-applying the same template skips duplicates', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create(['industry' => Industry::Handwerk->value]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::contacts.index')
        ->set('roleTemplateKey', Industry::Handwerk->value)
        ->call('loadRoleTemplate')
        ->call('loadRoleTemplate')
        ->assertHasNoErrors();

    $count = Contact::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('role', 'Geschäftsführung / Inhaber')
        ->count();

    expect($count)->toBe(1);
});

test('contacts page shows the rollen-vorlage button', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create(['industry' => Industry::Produktion->value]);

    $this->actingAs($user->fresh())
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('Rollen-Vorlage');
});
