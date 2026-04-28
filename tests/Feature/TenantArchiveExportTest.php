<?php

use App\Enums\TeamRole;
use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\User;
use App\Support\Backup\TenantArchiveExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('builds a zip archive containing data, audit log and pdfs', function () {
    Storage::fake('handbook');

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    $this->actingAs($user->fresh());

    $version = HandbookVersion::factory()->for($company)->create([
        'pdf_path' => 'v1.pdf',
        'pdf_hash' => 'abc',
        'pdf_size' => 4,
        'pdf_generated_at' => now(),
        'approved_at' => now(),
    ]);
    Storage::disk('handbook')->put('v1.pdf', 'PDF1');

    AuditLogEntry::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'entity_type' => 'Test',
        'entity_id' => 'abc',
        'entity_label' => 'Etwas',
        'action' => 'created',
        'changes' => ['foo' => 'bar'],
    ]);

    $path = TenantArchiveExporter::export($company);

    expect(file_exists($path))->toBeTrue();

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }

    expect($names)->toContain('daten.json');
    expect($names)->toContain('audit-log.csv');
    expect($names)->toContain('README.txt');

    $hasPdf = collect($names)->contains(fn (string $n) => str_starts_with($n, 'handbook-versions/') && str_ends_with($n, '.pdf'));
    expect($hasPdf)->toBeTrue();

    $csv = $zip->getFromName('audit-log.csv');
    expect($csv)->toContain('Etwas');
    expect($csv)->toContain('created');

    $readme = $zip->getFromName('README.txt');
    expect($readme)->toContain($company->name);

    $zip->close();
    @unlink($path);
});

it('downloads the archive via the controller route', function () {
    Storage::fake('handbook');

    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    $response = $this->actingAs($user->fresh())
        ->get(route('system-settings.archive.download', ['current_team' => $user->currentTeam->slug]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/zip');
});

it('forbids the archive route for non-admin users', function () {
    Storage::fake('handbook');

    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = $owner->currentTeam;
    Company::factory()->for($team)->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member->fresh())
        ->get(route('system-settings.archive.download', ['current_team' => $team->slug]))
        ->assertForbidden();
});
