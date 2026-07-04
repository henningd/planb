<?php

use App\Models\Company;
use App\Models\HandbookVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('the current handbook version is the newest approved one even on same-day approvals', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $older = HandbookVersion::factory()->for($company)->create([
        'version' => '1.0.0',
        'approved_at' => '2026-07-04',
        'changed_at' => '2026-07-04',
    ]);
    $older->forceFill(['created_at' => Carbon::parse('2026-07-04 09:00:00')])->saveQuietly();

    $newer = HandbookVersion::factory()->for($company)->create([
        'version' => '2.0.0',
        'approved_at' => '2026-07-04',
        'changed_at' => '2026-07-04',
    ]);
    $newer->forceFill(['created_at' => Carbon::parse('2026-07-04 15:00:00')])->saveQuietly();

    expect($company->currentHandbookVersion()?->id)->toBe($newer->id);
});
