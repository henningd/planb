<?php

use App\Enums\PreventiveMeasureInterval;
use App\Enums\PreventiveMeasureStatus;
use App\Enums\SystemType;
use App\Models\PreventiveMeasure;
use App\Models\System;
use App\Support\Prevention\PreventiveMeasureCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('markExecuted on a recurring measure sets the next due date from the interval', function () {
    $system = System::factory()->create();
    $measure = PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Quarterly,
        'status' => PreventiveMeasureStatus::Planned,
        'next_due_at' => '2026-06-15',
    ]);

    $measure->markExecuted(CarbonImmutable::parse('2026-06-15'));

    expect($measure->last_executed_at->format('Y-m-d'))->toBe('2026-06-15')
        ->and($measure->next_due_at->format('Y-m-d'))->toBe('2026-09-15')
        ->and($measure->status)->toBe(PreventiveMeasureStatus::Active);
});

test('markExecuted on a one-time measure activates it without a next due date', function () {
    $system = System::factory()->create();
    $measure = PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => null,
        'status' => PreventiveMeasureStatus::InProgress,
    ]);

    $measure->markExecuted(CarbonImmutable::parse('2026-06-15'));

    expect($measure->status)->toBe(PreventiveMeasureStatus::Active)
        ->and($measure->last_executed_at->format('Y-m-d'))->toBe('2026-06-15')
        ->and($measure->next_due_at)->toBeNull();
});

test('isOverdue reflects the next due date but ignores paused measures', function () {
    $system = System::factory()->create();

    $overdue = PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Active,
        'next_due_at' => now()->subDay()->format('Y-m-d'),
    ]);

    $paused = PreventiveMeasure::factory()->forSystem($system)->create([
        'interval' => PreventiveMeasureInterval::Monthly,
        'status' => PreventiveMeasureStatus::Paused,
        'next_due_at' => now()->subDay()->format('Y-m-d'),
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($paused->isOverdue())->toBeFalse();
});

test('the recurringDue scope only returns recurring, non-paused measures with a due date', function () {
    $system = System::factory()->create();

    PreventiveMeasure::factory()->forSystem($system)->recurring()->create();
    PreventiveMeasure::factory()->forSystem($system)->create(['interval' => null]); // einmalig
    PreventiveMeasure::factory()->forSystem($system)->recurring()->create([
        'status' => PreventiveMeasureStatus::Paused,
    ]);

    expect(PreventiveMeasure::recurringDue()->count())->toBe(1);
});

test('the catalog suggests common measures plus type-specific ones', function () {
    $common = PreventiveMeasureCatalog::forSystemType(null);
    $server = PreventiveMeasureCatalog::forSystemType(SystemType::Server);

    expect($common)->not->toBeEmpty()
        ->and(count($server))->toBeGreaterThan(count($common))
        ->and(collect($server)->pluck('title'))->toContain('USV-Wartung & Test');
});
