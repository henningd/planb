<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('saves display_name and primary_color through the branding page', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['name' => 'Acme GmbH']);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->set('display_name', 'Acme Cyber Defense')
        ->set('primary_color', '#10b981')
        ->call('save')
        ->assertHasNoErrors();

    expect($company->fresh()->display_name)->toBe('Acme Cyber Defense');
    expect($company->fresh()->primary_color)->toBe('#10b981');
});

it('rejects an invalid hex color', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->set('primary_color', 'green')
        ->call('save')
        ->assertHasErrors(['primary_color']);
});

it('uploads a logo file and stores it on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $file = UploadedFile::fake()->create('logo.png', 12, 'image/png');

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->set('logo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $company->refresh();
    expect($company->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($company->logo_path);
});

it('removes a logo and deletes the underlying file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Storage::disk('public')->put('company-logos/'.$company->id.'/old.png', 'fake');
    $company->forceFill(['logo_path' => 'company-logos/'.$company->id.'/old.png'])->save();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->call('removeLogo');

    expect($company->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing('company-logos/'.$company->id.'/old.png');
});

it('falls back to the company name when display_name is empty', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create([
        'name' => 'Mustermann GmbH',
        'display_name' => null,
    ]);

    expect($company->brandName())->toBe('Mustermann GmbH');

    $company->display_name = 'Mustermann Cyber';
    expect($company->brandName())->toBe('Mustermann Cyber');
});

it('returns the platform default color when none is configured', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['primary_color' => null]);

    expect($company->brandColor())->toBe('#4f46e5');
});

it('rejects malformed hex colors at the model level', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create(['primary_color' => 'not-a-color']);

    expect($company->brandColor())->toBe('#4f46e5');
});

it('replaces an existing logo on re-upload and removes the old file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    Storage::disk('public')->put('company-logos/'.$company->id.'/old.png', 'fake');
    $company->forceFill(['logo_path' => 'company-logos/'.$company->id.'/old.png'])->save();

    $newFile = UploadedFile::fake()->create('new.png', 12, 'image/png');

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->set('logo', $newFile)
        ->call('save');

    Storage::disk('public')->assertMissing('company-logos/'.$company->id.'/old.png');
    expect($company->fresh()->logo_path)->not->toBe('company-logos/'.$company->id.'/old.png');
});

it('keeps display_name and color in sync after pages render', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create([
        'display_name' => 'Acme',
        'primary_color' => '#ff0000',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::branding.index')
        ->assertSet('display_name', 'Acme')
        ->assertSet('primary_color', '#ff0000');
});

it('forbids non-admin users on the branding route', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    Company::factory()->for($team)->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->get(route('branding.index', ['current_team' => $team->slug]))
        ->assertForbidden();
});
