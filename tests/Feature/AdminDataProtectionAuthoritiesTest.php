<?php

use App\Models\DataProtectionAuthority;
use App\Models\User;
use Database\Seeders\DataProtectionAuthoritiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function dpaAdmin(): User
{
    return User::factory()->create(['is_super_admin' => true])->fresh();
}

test('non-super-admins are blocked', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user->fresh())
        ->get(route('admin.data-protection-authorities.index'))
        ->assertForbidden();
});

test('super-admin sees the index with seeded authorities', function () {
    $this->seed(DataProtectionAuthoritiesSeeder::class);

    $this->actingAs(dpaAdmin())
        ->get(route('admin.data-protection-authorities.index'))
        ->assertOk()
        ->assertSee('Datenschutz-Aufsichtsbehörden')
        ->assertSee('LfDI Baden-Württemberg')
        ->assertSee('BayLDA');
});

test('admin can create a new authority and is redirected to its show page', function () {
    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.index')
        ->set('key', 'demo-dpa')
        ->set('name', 'Demo-Datenschutzbehörde')
        ->set('short_name', 'DDB')
        ->set('state', 'Demo-Land')
        ->set('city', 'Demohausen')
        ->set('sort', 100)
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(DataProtectionAuthority::where('key', 'demo-dpa')->exists())->toBeTrue();
});

test('key must be unique on create', function () {
    DataProtectionAuthority::create([
        'key' => 'existing',
        'name' => 'Bestehend',
        'sort' => 1,
    ]);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.index')
        ->set('key', 'existing')
        ->set('name', 'Doppelt')
        ->call('create')
        ->assertHasErrors(['key' => 'unique']);
});

test('admin can delete an authority', function () {
    $authority = DataProtectionAuthority::create([
        'key' => 'to-delete',
        'name' => 'Zu löschen',
        'sort' => 1,
    ]);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.index')
        ->call('confirmDelete', $authority->id)
        ->call('delete');

    expect(DataProtectionAuthority::find($authority->id))->toBeNull();
});

test('reseed restores the seeded defaults', function () {
    DataProtectionAuthority::create([
        'key' => 'lfdi-bw',
        'name' => 'Manuell überschrieben',
        'sort' => 999,
    ]);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.index')
        ->call('reseed');

    $bw = DataProtectionAuthority::where('key', 'lfdi-bw')->first();
    expect($bw->name)->toBe('LfDI Baden-Württemberg');
    expect($bw->postalCodeRanges()->count())->toBeGreaterThan(0);
});

test('admin can edit master data on the show page', function () {
    $this->seed(DataProtectionAuthoritiesSeeder::class);
    $authority = DataProtectionAuthority::where('key', 'lfdi-bw')->first();

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->set('phone', '+49 711 NEU')
        ->set('notes', 'Manuelle Notiz')
        ->call('saveMeta')
        ->assertHasNoErrors();

    $authority->refresh();
    expect($authority->phone)->toBe('+49 711 NEU')
        ->and($authority->notes)->toBe('Manuelle Notiz');
});

test('admin can add a PLZ range', function () {
    $authority = DataProtectionAuthority::create([
        'key' => 'demo',
        'name' => 'Demo',
        'sort' => 1,
    ]);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->set('rangeFrom', '12000')
        ->set('rangeTo', '12999')
        ->set('rangeNotes', 'Demo-Bereich')
        ->call('saveRange')
        ->assertHasNoErrors();

    expect($authority->postalCodeRanges()->count())->toBe(1);
    $range = $authority->postalCodeRanges()->first();
    expect($range->plz_from)->toBe('12000')
        ->and($range->plz_to)->toBe('12999')
        ->and($range->notes)->toBe('Demo-Bereich');
});

test('range validation rejects non-5-digit input and inverted bounds', function () {
    $authority = DataProtectionAuthority::create([
        'key' => 'demo2',
        'name' => 'Demo2',
        'sort' => 1,
    ]);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->set('rangeFrom', '123')
        ->set('rangeTo', '456')
        ->call('saveRange')
        ->assertHasErrors(['rangeFrom']);

    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->set('rangeFrom', '50000')
        ->set('rangeTo', '40000')
        ->call('saveRange')
        ->assertHasErrors(['rangeTo']);
});

test('admin can edit and delete a PLZ range', function () {
    $authority = DataProtectionAuthority::create([
        'key' => 'demo3',
        'name' => 'Demo3',
        'sort' => 1,
    ]);
    $range = $authority->postalCodeRanges()->create([
        'plz_from' => '10000',
        'plz_to' => '10999',
    ]);

    // edit
    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->call('openEditRange', $range->id)
        ->set('rangeFrom', '20000')
        ->set('rangeTo', '20999')
        ->call('saveRange')
        ->assertHasNoErrors();

    expect($range->fresh()->plz_from)->toBe('20000');

    // delete
    Livewire::actingAs(dpaAdmin())
        ->test('pages::admin.data-protection-authorities.show', ['authority' => $authority])
        ->call('confirmDeleteRange', $range->id)
        ->call('deleteRange');

    expect($authority->postalCodeRanges()->count())->toBe(0);
});

test('admin index page links from /admin overview', function () {
    $this->actingAs(dpaAdmin())
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee(__('Datenschutz-Aufsichtsbehörden'))
        ->assertSee(route('admin.data-protection-authorities.index'));
});
