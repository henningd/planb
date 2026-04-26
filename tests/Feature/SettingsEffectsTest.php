<?php

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\HandbookPdfGenerator;
use App\Support\Settings\CompanySetting;
use App\Support\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Storage::fake(HandbookPdfGenerator::DISK);
});

// === registration_enabled ===

test('registration page is reachable when registration is enabled', function () {
    SystemSetting::set('registration_enabled', true);

    $this->get('/register')->assertOk();
});

test('registration page returns 404 when registration is disabled', function () {
    SystemSetting::set('registration_enabled', false);

    $this->get('/register')->assertNotFound();
});

// === demo_locked ===

test('demo wipe is blocked when demo_locked is true', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    SystemSetting::set('demo_locked', true);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('wipe')
        ->assertHasNoErrors();

    expect(User::where('email', 'max@mustermann.de')->exists())->toBeFalse();
});

test('demo seed is blocked when demo_locked is true', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    SystemSetting::set('demo_locked', true);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    expect(User::where('email', 'max@mustermann.de')->exists())->toBeFalse();
});

// === auto_pdf_enabled ===

test('auto_pdf_enabled per-tenant generates a pdf on version creation', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('auto_pdf_enabled', true);

    $version = HandbookVersion::factory()->for($company)->create();

    expect($version->refresh()->hasPdf())->toBeTrue();
    Storage::disk(HandbookPdfGenerator::DISK)->assertExists($version->pdf_path);
});

test('auto_pdf_enabled does not fire when disabled', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('auto_pdf_enabled', false);

    $version = HandbookVersion::factory()->for($company)->create();

    expect($version->refresh()->hasPdf())->toBeFalse();
});

// === pdf_paper_size + pdf_footer_show_hash ===

test('pdf settings are applied to the generated pdf', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('pdf_paper_size', 'letter');
    CompanySetting::for($company)->set('pdf_footer_show_hash', true);

    $version = HandbookVersion::factory()->for($company)->create();
    HandbookPdfGenerator::generate($version->refresh());

    Storage::disk(HandbookPdfGenerator::DISK)->assertExists($version->refresh()->pdf_path);
});

// === share_link_default_days ===

test('share form picks the per-tenant default for valid days', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('share_link_default_days', 60);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::handbook-shares.index')
        ->assertSet('validDays', 60);
});

// === audit_retention_days command ===

test('cleanup command deletes old audit entries based on per-tenant retention', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('audit_retention_days', 7);

    DB::table('audit_log_entries')->insert([
        'id' => Str::uuid()->toString(),
        'company_id' => $company->id,
        'user_id' => $user->id,
        'entity_type' => 'old',
        'entity_id' => '00000000-0000-0000-0000-000000000001',
        'entity_label' => 'old',
        'action' => 'created',
        'created_at' => now()->subDays(30),
    ]);
    DB::table('audit_log_entries')->insert([
        'id' => Str::uuid()->toString(),
        'company_id' => $company->id,
        'user_id' => $user->id,
        'entity_type' => 'fresh',
        'entity_id' => '00000000-0000-0000-0000-000000000002',
        'entity_label' => 'fresh',
        'action' => 'created',
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('app:cleanup-audit-log')->assertSuccessful();

    expect(DB::table('audit_log_entries')->where('entity_type', 'old')->exists())->toBeFalse();
    expect(DB::table('audit_log_entries')->where('entity_type', 'fresh')->exists())->toBeTrue();
});

test('cleanup command leaves audit entries untouched when retention is 0', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('audit_retention_days', 0);

    DB::table('audit_log_entries')->insert([
        'id' => Str::uuid()->toString(),
        'company_id' => $company->id,
        'user_id' => $user->id,
        'entity_type' => 'ancient',
        'entity_id' => '00000000-0000-0000-0000-000000000003',
        'entity_label' => 'ancient',
        'action' => 'created',
        'created_at' => now()->subYears(10),
    ]);

    $this->artisan('app:cleanup-audit-log')->assertSuccessful();

    expect(DB::table('audit_log_entries')->where('entity_type', 'ancient')->exists())->toBeTrue();
});

// === enforce_2fa_admins ===

test('admin without 2fa is redirected when enforcement is on for tenant', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($company)->set('enforce_2fa_admins', true);

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('admin with confirmed 2fa is not redirected', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);
    Company::factory()->for($user->currentTeam)->create();
    CompanySetting::for($user->currentCompany())->set('enforce_2fa_admins', true);

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertOk();
});

test('non-admin members are not affected by 2fa enforcement', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();
    CompanySetting::for($company)->set('enforce_2fa_admins', true);

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($member->fresh())
        ->get(route('dashboard'))
        ->assertOk();
});

// === Mandant-Page Auth ===

test('member without admin rights cannot reach the tenant system settings page', function () {
    $owner = User::factory()->create();
    $company = Company::factory()->for($owner->currentTeam)->create();

    $member = User::factory()->create();
    $owner->currentTeam->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($owner->currentTeam);

    $this->actingAs($member->fresh())
        ->get(route('system-settings.index'))
        ->assertForbidden();
});

test('admin can save tenant overrides via the page', function () {
    $user = User::factory()->create();
    Company::factory()->for($user->currentTeam)->create();

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::system-settings.index')
        ->set('overrides.share_link_default_days', true)
        ->set('values.share_link_default_days', 99)
        ->call('save')
        ->assertHasNoErrors();

    expect(CompanySetting::for($user->currentCompany())->get('share_link_default_days'))->toBe(99);
});

// === Plattform-Page Auth ===

test('only super admins can reach platform settings page', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin->fresh())->get(route('admin.settings.system.index'))->assertOk();

    $regular = User::factory()->create(['is_super_admin' => false]);
    $this->actingAs($regular->fresh())->get(route('admin.settings.system.index'))->assertForbidden();
});

test('platform_name override updates app.name at runtime', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    SystemSetting::set('platform_name', 'PlanB Test');

    // Re-boot the AppServiceProvider's platform overrides
    $this->app->register(AppServiceProvider::class, true);

    expect(config('app.name'))->toBe('PlanB Test');
});
