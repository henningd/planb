<?php

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\User;
use App\Support\HandbookPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(HandbookPdfGenerator::DISK);
});

test('release action generates a pdf and writes metadata to the version', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $version = HandbookVersion::factory()->for($company)->create([
        'version' => '1.0',
        'changed_at' => '2026-04-01',
        'approved_at' => null,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->call('releasePdf', $version->id)
        ->assertHasNoErrors();

    $version->refresh();
    expect($version->pdf_path)->toBe("{$company->id}/{$version->id}.pdf");
    expect($version->pdf_hash)->toMatch('/^[a-f0-9]{64}$/');
    expect($version->pdf_size)->toBeGreaterThan(0);
    expect($version->pdf_generated_at)->not->toBeNull();
    expect($version->approved_at)->not->toBeNull();

    Storage::disk(HandbookPdfGenerator::DISK)->assertExists($version->pdf_path);

    $stored = Storage::disk(HandbookPdfGenerator::DISK)->get($version->pdf_path);
    expect(substr($stored, 0, 4))->toBe('%PDF');
});

test('a released version cannot be released again (immutability)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();

    HandbookPdfGenerator::generate($version->refresh());
    $firstHash = $version->refresh()->pdf_hash;

    expect(fn () => HandbookPdfGenerator::generate($version->refresh()))
        ->toThrow(RuntimeException::class);

    expect($version->refresh()->pdf_hash)->toBe($firstHash);
});

test('a released version cannot be deleted via the ui (revision-safe)', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();

    HandbookPdfGenerator::generate($version->refresh());

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->set('deletingId', $version->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(HandbookVersion::find($version->id))->not->toBeNull();
    Storage::disk(HandbookPdfGenerator::DISK)->assertExists($version->refresh()->pdf_path);
});

test('deleting a version without pdf removes it normally', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $version = HandbookVersion::factory()->for($user->currentCompany())->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-versions.index')
        ->set('deletingId', $version->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(HandbookVersion::find($version->id))->toBeNull();
});

test('owner can download the pdf of their own version', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();
    HandbookPdfGenerator::generate($version->refresh());

    $this->actingAs($user->fresh())
        ->get(route('handbook-versions.pdf', [
            'current_team' => $user->currentTeam->slug,
            'version' => $version->id,
        ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('cross-tenant access is blocked even with a known version id', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();
    HandbookPdfGenerator::generate($version->refresh());

    $other = User::factory()->create();
    Company::factory()->for($other->currentTeam)->create();

    $this->actingAs($other->fresh())
        ->get(route('handbook-versions.pdf', [
            'current_team' => $other->currentTeam->slug,
            'version' => $version->id,
        ]))
        ->assertNotFound();
});

test('download requires an existing pdf', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();

    $this->actingAs($user->fresh())
        ->get(route('handbook-versions.pdf', [
            'current_team' => $user->currentTeam->slug,
            'version' => $version->id,
        ]))
        ->assertNotFound();
});

test('download requires authentication', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $version = HandbookVersion::factory()->for($company)->create();

    $this->get(route('handbook-versions.pdf', [
        'current_team' => $user->currentTeam->slug,
        'version' => $version->id,
    ]))->assertRedirect(route('login'));
});
