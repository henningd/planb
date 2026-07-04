<?php

use App\Models\Company;
use App\Models\User;
use App\Support\HandbookData;
use App\Support\HandbookPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function handbookPrintCompany(): Company
{
    $user = User::factory()->create();

    return Company::factory()->for($user->currentTeam)->create();
}

test('the browser print view keeps the toolbar and uses the asset url', function () {
    $company = handbookPrintCompany();

    $html = view('handbook-print', HandbookData::forCompany($company))->render();

    expect($html)->toContain('Als PDF speichern');
});

test('the pdf render omits the toolbar and embeds the emblem via a local path', function () {
    $company = handbookPrintCompany();

    $data = HandbookData::forCompany($company);
    $data['isPdf'] = true;

    $html = view('handbook-print', $data)->render();

    expect($html)->not->toContain('Als PDF speichern')
        ->and($html)->toContain(public_path('wappen.png'));
});

test('renderLive produces a real pdf', function () {
    $company = handbookPrintCompany();

    expect(substr(HandbookPdfGenerator::renderLive($company), 0, 4))->toBe('%PDF');
});
