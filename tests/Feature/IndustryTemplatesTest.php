<?php

use App\Enums\Industry;
use App\Models\Company;
use App\Models\IndustryTemplate;
use App\Models\Location;
use App\Models\User;
use App\Support\Backup\Exporter;
use App\Support\HandbookPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(HandbookPdfGenerator::DISK);
});

test('non super admins are blocked from the industry templates page', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user->fresh())
        ->get(route('admin.industry-templates.index'))
        ->assertForbidden();
});

test('super admin sees the templates page', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.industry-templates.index'))
        ->assertOk()
        ->assertSee('Branchen-Templates');
});

test('snapshot from a company creates a template with payload', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $owner = User::factory()->create();
    $source = Company::factory()->for($owner->currentTeam)->create(['name' => 'Source GmbH']);
    Location::factory()->for($source)->create(['name' => 'HQ Source']);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.industry-templates.index')
        ->call('openCreate')
        ->set('name', 'Handwerk Standard')
        ->set('industry', Industry::Handwerk->value)
        ->set('payloadMode', 'snapshot')
        ->set('snapshotCompanyId', $source->id)
        ->call('save')
        ->assertHasNoErrors();

    $tpl = IndustryTemplate::firstWhere('name', 'Handwerk Standard');
    expect($tpl)->not->toBeNull();
    expect($tpl->industry)->toBe(Industry::Handwerk);
    expect($tpl->payload)->toBeArray();
    expect($tpl->payload['areas']['locations'] ?? null)->not->toBeNull();
    expect(collect($tpl->payload['areas']['locations'])->pluck('name')->all())->toContain('HQ Source');
});

test('apply replaces target company data with template payload', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $sourceUser = User::factory()->create();
    $source = Company::factory()->for($sourceUser->currentTeam)->create();
    Location::factory()->for($source)->create(['name' => 'Source-Standort']);

    $tpl = IndustryTemplate::create([
        'name' => 'Test',
        'industry' => Industry::Handwerk,
        'payload' => Exporter::export(
            $source,
            ['locations'],
        ),
        'is_active' => true,
        'sort' => 0,
    ]);

    $targetUser = User::factory()->create();
    $target = Company::factory()->for($targetUser->currentTeam)->create();
    Location::factory()->for($target)->create(['name' => 'Alt-Standort']);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.industry-templates.index')
        ->call('openApply', $tpl->id)
        ->set('applyTargetCompanyId', $target->id)
        ->call('confirmApply')
        ->call('runApply')
        ->assertHasNoErrors();

    $names = DB::table('locations')->where('company_id', $target->id)->pluck('name')->all();
    expect($names)->toContain('Source-Standort');
    expect($names)->not->toContain('Alt-Standort');
});

test('upload mode rejects malformed json', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.industry-templates.index')
        ->call('openCreate')
        ->set('name', 'X')
        ->set('industry', Industry::Handwerk->value)
        ->set('payloadMode', 'upload')
        ->call('save')
        ->assertHasErrors(['payloadMode']); // Kein Payload, kein Snapshot, kein File → Fehler beim Anlegen
});

test('admin can edit a template without changing payload (keep mode)', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $tpl = IndustryTemplate::create([
        'name' => 'Alt',
        'industry' => Industry::Handwerk,
        'payload' => ['areas' => ['locations' => []]],
        'is_active' => true,
        'sort' => 0,
    ]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.industry-templates.index')
        ->call('openEdit', $tpl->id)
        ->set('name', 'Neu')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = IndustryTemplate::find($tpl->id);
    expect($fresh->name)->toBe('Neu');
    expect($fresh->payload)->toBe(['areas' => ['locations' => []]]); // unverändert
});

test('admin can delete a template', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $tpl = IndustryTemplate::create([
        'name' => 'Weg',
        'industry' => Industry::Handwerk,
        'payload' => null,
        'is_active' => true,
        'sort' => 0,
    ]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.industry-templates.index')
        ->call('confirmDelete', $tpl->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(IndustryTemplate::find($tpl->id))->toBeNull();
});
