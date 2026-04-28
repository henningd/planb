<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Settings\CompanySetting;
use App\Support\Settings\SettingsCatalog;
use App\Support\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('SystemSetting falls back to catalog default when nothing is stored', function () {
    expect(SystemSetting::get('registration_enabled'))->toBeTrue();
    expect(SystemSetting::get('demo_locked'))->toBeFalse();
    expect(SystemSetting::get('share_link_default_days'))->toBe(30);
});

test('SystemSetting persists and reads correctly with type casting', function () {
    SystemSetting::set('registration_enabled', '0');
    SystemSetting::set('share_link_default_days', '14');
    SystemSetting::set('pdf_paper_size', 'letter');
    SystemSetting::set('pdf_paper_size', 'invalid');

    expect(SystemSetting::get('registration_enabled'))->toBeFalse();
    expect(SystemSetting::get('share_link_default_days'))->toBe(14);
    expect(SystemSetting::get('pdf_paper_size'))->toBe('a4');
});

test('CompanySetting falls back to system value, then catalog default', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    // Catalog default = false
    expect(CompanySetting::for($company)->get('auto_pdf_enabled'))->toBeFalse();

    // System override -> applies to all companies without their own override
    SystemSetting::set('auto_pdf_enabled', true);
    Cache::flush();
    expect(CompanySetting::for($company)->get('auto_pdf_enabled'))->toBeTrue();

    // Company override beats system
    CompanySetting::for($company)->set('auto_pdf_enabled', false);
    expect(CompanySetting::for($company)->get('auto_pdf_enabled'))->toBeFalse();
    expect(CompanySetting::for($company)->isOverridden('auto_pdf_enabled'))->toBeTrue();

    // Unsetting falls back again
    CompanySetting::for($company)->unset('auto_pdf_enabled');
    expect(CompanySetting::for($company)->get('auto_pdf_enabled'))->toBeTrue();
    expect(CompanySetting::for($company)->isOverridden('auto_pdf_enabled'))->toBeFalse();
});

test('CompanySetting isolates between companies', function () {
    $userA = User::factory()->create();
    $companyA = Company::factory()->for($userA->currentTeam)->create();
    $userB = User::factory()->create();
    $companyB = Company::factory()->for($userB->currentTeam)->create();

    CompanySetting::for($companyA)->set('auto_pdf_enabled', true);

    expect(CompanySetting::for($companyA)->get('auto_pdf_enabled'))->toBeTrue();
    expect(CompanySetting::for($companyB)->get('auto_pdf_enabled'))->toBeFalse();
});

test('catalog scope filtering returns the right keys', function () {
    expect(array_keys(SettingsCatalog::byScope(SettingsCatalog::SYSTEM)))
        ->toEqualCanonicalizing([
            'registration_enabled',
            'demo_locked',
            'platform_name',
            'platform_footer',
            'platform_contact_email',
            'platform_contact_phone',
            'platform_imprint',
            'platform_privacy',
            'platform_terms',
            'platform_av_contract',
            'platform_tom',
            'platform_subprocessors',
            'platform_accessibility',
            'platform_security_contact',
            'platform_status_state',
            'platform_status_incidents',
        ]);

    expect(array_keys(SettingsCatalog::byScope(SettingsCatalog::COMPANY)))
        ->toEqualCanonicalizing([
            'auto_pdf_enabled',
            'incident_mode_enabled',
            'enforce_2fa_admins',
            'share_link_default_days',
            'audit_retention_days',
            'pdf_paper_size',
            'pdf_footer_show_hash',
            'slack_webhook_url',
            'teams_webhook_url',
        ]);
});
