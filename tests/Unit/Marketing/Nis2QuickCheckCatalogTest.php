<?php

use App\Enums\Nis2Readiness;
use App\Support\Marketing\Nis2QuickCheckCatalog;

test('the catalog exposes ten questions with a maximum score of twenty', function () {
    expect(Nis2QuickCheckCatalog::allKeys())->toHaveCount(10)
        ->and(Nis2QuickCheckCatalog::maxScore())->toBe(20);
});

test('scoreFor sums the answer points and treats missing answers as no', function () {
    $allYes = collect(Nis2QuickCheckCatalog::allKeys())
        ->mapWithKeys(fn (string $key) => [$key => 'yes'])
        ->all();

    expect(Nis2QuickCheckCatalog::scoreFor($allYes))->toBe(20)
        ->and(Nis2QuickCheckCatalog::scoreFor([]))->toBe(0)
        ->and(Nis2QuickCheckCatalog::scoreFor(['betroffenheit_geklaert' => 'partial']))->toBe(1);
});

test('readinessForScore maps the score ratio to the right stage', function (int $score, Nis2Readiness $expected) {
    expect(Nis2QuickCheckCatalog::readinessForScore($score, 20))->toBe($expected);
})->with([
    'zero is critical' => [0, Nis2Readiness::Kritisch],
    'just below 40% is critical' => [7, Nis2Readiness::Kritisch],
    'exactly 40% is building' => [8, Nis2Readiness::Aufbau],
    'just below 80% is building' => [15, Nis2Readiness::Aufbau],
    'exactly 80% is solid' => [16, Nis2Readiness::Solide],
    'full score is solid' => [20, Nis2Readiness::Solide],
]);
