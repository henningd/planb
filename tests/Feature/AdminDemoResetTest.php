<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\HandbookShare;
use App\Models\HandbookVersion;
use App\Models\SystemTask;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\HandbookPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ohne fake würden DemoDataSeeder-PDFs in echte Storage-Pfade landen.
    Storage::fake(HandbookPdfGenerator::DISK);
});

test('non super admins are blocked from the demo reset page', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user->fresh())
        ->get(route('admin.demo.index'))
        ->assertForbidden();
});

test('super admin sees the demo reset page', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.demo.index'))
        ->assertOk()
        ->assertSee('Demo-Firma')
        ->assertSee('max@mustermann.de');
});

test('seed action creates the demo user, team and company', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $user = User::where('email', 'max@mustermann.de')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->is_super_admin)->toBeTrue();

    $team = $user->ownedTeams()->first();
    expect($team)->not->toBeNull();

    $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('team_id', $team->id)
        ->first();
    expect($company)->not->toBeNull();
    expect($company->name)->toBe('Musterfirma GmbH');

    $employees = Employee::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->count();
    expect($employees)->toBeGreaterThan(0);
});

test('seed populates system_tasks across multiple systems', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('name', 'Musterfirma GmbH')
        ->first();

    $tasks = SystemTask::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->get();

    expect($tasks->count())->toBeGreaterThanOrEqual(10);
    // Mindestens ein paar Tasks sollten erledigt, ein paar offen sein.
    expect($tasks->whereNotNull('completed_at')->count())->toBeGreaterThan(0);
    expect($tasks->whereNull('completed_at')->count())->toBeGreaterThan(0);
});

test('seed creates revision-safe pdfs for approved handbook versions and shares', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $company = Company::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('name', 'Musterfirma GmbH')
        ->first();

    $approved = HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNotNull('approved_at')
        ->get();

    expect($approved)->toHaveCount(2);
    foreach ($approved as $v) {
        expect($v->pdf_path)->not->toBeNull();
        expect($v->pdf_hash)->toMatch('/^[a-f0-9]{64}$/');
        Storage::disk(HandbookPdfGenerator::DISK)->assertExists($v->pdf_path);
    }

    $pending = HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->whereNull('approved_at')
        ->first();
    expect($pending)->not->toBeNull();
    expect($pending->pdf_path)->toBeNull();

    $shares = HandbookShare::withoutGlobalScope(CurrentCompanyScope::class)
        ->where('company_id', $company->id)
        ->get();
    expect($shares)->toHaveCount(3);
});

test('seed action also creates the secondary demo user as team admin', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $secondary = User::where('email', 'maxigreis@icloud.com')->first();
    expect($secondary)->not->toBeNull();
    expect($secondary->name)->toBe('Maxi Greis');
    expect(Hash::check('passworD321!1', $secondary->password))->toBeTrue();
    expect($secondary->is_super_admin)->toBeFalse();

    $team = User::where('email', 'max@mustermann.de')->first()->ownedTeams()->first();
    expect($secondary->belongsToTeam($team))->toBeTrue();
    expect($secondary->teamRole($team)?->value)->toBe('admin');
});

test('seed resets the secondary password too', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    User::factory()->create([
        'name' => 'Maxi Greis',
        'email' => 'maxigreis@icloud.com',
        'password' => Hash::make('something-else'),
    ]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $secondary = User::where('email', 'maxigreis@icloud.com')->first();
    expect(Hash::check('passworD321!1', $secondary->password))->toBeTrue();
});

test('seed resets the password to "password" even if it was changed', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    User::factory()->create([
        'name' => 'Max Mustermann',
        'email' => 'max@mustermann.de',
        'password' => Hash::make('something-else'),
    ]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->assertHasNoErrors();

    $user = User::where('email', 'max@mustermann.de')->first();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

test('wipe action removes both demo users and cascades to all related data', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->call('wipe')
        ->assertHasNoErrors();

    expect(User::where('email', 'max@mustermann.de')->exists())->toBeFalse();
    expect(User::where('email', 'maxigreis@icloud.com')->exists())->toBeFalse();
    expect(Team::withTrashed()->where('slug', 'max-mustermanns-team')->exists())->toBeFalse();
    expect(
        Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->withTrashed()
            ->where('name', 'Musterfirma GmbH')
            ->exists()
    )->toBeFalse();
});

test('wipe leaves other tenants untouched', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $otherUser = User::factory()->create();
    $otherCompany = Company::factory()->for($otherUser->currentTeam)->create([
        'name' => 'Andere Firma',
    ]);

    Livewire\Livewire::actingAs($admin->fresh())
        ->test('pages::admin.demo.index')
        ->call('seed')
        ->call('wipe')
        ->assertHasNoErrors();

    expect(User::where('email', $otherUser->email)->exists())->toBeTrue();
    expect(
        Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('id', $otherCompany->id)
            ->exists()
    )->toBeTrue();
});
