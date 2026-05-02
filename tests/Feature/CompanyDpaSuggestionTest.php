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

test('Card-Klick wählt Behörde aus und füllt Name/Telefon/Website', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $authority = App\Models\DataProtectionAuthority::where('key', 'lfdi-bw')->first();

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->call('selectAuthority', $authority->id)
        ->assertSet('authority_mode', 'list')
        ->assertSet('selected_authority_id', $authority->id)
        ->assertSet('data_protection_authority_name', 'LfDI Baden-Württemberg')
        ->assertSet('data_protection_authority_phone', '+49 711 615541-0');
});

test('Klick auf Benutzerdefiniert-Card wechselt in Custom-Modus', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'data_protection_authority_name' => 'LfDI Baden-Württemberg',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSet('authority_mode', 'list')
        ->call('selectCustom')
        ->assertSet('authority_mode', 'custom')
        ->assertSet('selected_authority_id', null);
});

test('Mount erkennt bestehenden Custom-Eintrag', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'data_protection_authority_name' => 'Spezial-Behörde nicht in Liste',
    ]);

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSet('authority_mode', 'custom')
        ->assertSet('selected_authority_id', null);
});

test('Mount erkennt bestehende Listen-Auswahl per Name-Match', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'data_protection_authority_name' => 'Berliner Beauftragte für Datenschutz und Informationsfreiheit',
    ]);

    $expectedId = App\Models\DataProtectionAuthority::where('key', 'blnbdi')->value('id');

    Livewire::actingAs($user->fresh())
        ->test('pages::company.edit')
        ->assertSet('authority_mode', 'list')
        ->assertSet('selected_authority_id', $expectedId);
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
