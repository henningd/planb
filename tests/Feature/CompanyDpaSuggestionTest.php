<?php

use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\DataProtectionAuthoritiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DataProtectionAuthoritiesSeeder::class);
});

test('Vorschlag erscheint, wenn HQ-PLZ einer Behörde zuzuordnen ist', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    Location::factory()->for($company)->create([
        'is_headquarters' => true,
        'postal_code' => '70173',
        'city' => 'Stuttgart',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSeeHtml('data-test="dpa-suggestion"')
        ->assertSee('LfDI Baden-Württemberg');
});

test('„Übernehmen"-Button füllt Name, Telefon, Website', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    Location::factory()->for($company)->create([
        'is_headquarters' => true,
        'postal_code' => '80331',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->call('applySuggestedAuthority')
        ->assertSet('data_protection_authority_name', 'Bayerisches Landesamt für Datenschutzaufsicht (BayLDA)')
        ->assertSet('data_protection_authority_phone', '+49 981 180093-0')
        ->assertSet('data_protection_authority_website', 'https://www.lda.bayern.de');
});

test('Bestätigungs-Hinweis wenn Zuordnung bereits passt', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'data_protection_authority_name' => 'LfDI Baden-Württemberg',
    ]);
    Location::factory()->for($company)->create([
        'is_headquarters' => true,
        'postal_code' => '70173',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSeeHtml('data-test="dpa-suggestion-match"');
});

test('Warnung bei PLZ ohne Zuordnung', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    Location::factory()->for($company)->create([
        'is_headquarters' => true,
        'postal_code' => '00000',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSee(__('Für PLZ :plz konnte keine Aufsichtsbehörde automatisch zugeordnet werden — bitte manuell pflegen.', ['plz' => '00000']));
});

test('keine Zuordnung wenn gar kein Standort hinterlegt ist', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertDontSeeHtml('data-test="dpa-suggestion"');
});

test('manueller Wert bleibt erhalten — Vorschlag überschreibt nicht automatisch', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'data_protection_authority_name' => 'Eigene manuelle Eingabe',
    ]);
    Location::factory()->for($company)->create([
        'is_headquarters' => true,
        'postal_code' => '70173',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSet('data_protection_authority_name', 'Eigene manuelle Eingabe')
        ->assertSeeHtml('data-test="dpa-suggestion'); // Vorschlag wird angezeigt, aber nicht angewandt
});
