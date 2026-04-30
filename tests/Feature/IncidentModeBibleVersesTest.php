<?php

use App\Support\BibleVerses;
use Illuminate\Support\Facades\Config;

test('peace and crisis lists each contain 20 verses with text and reference', function () {
    expect(BibleVerses::peace())->toHaveCount(20);
    expect(BibleVerses::crisis())->toHaveCount(20);

    foreach (BibleVerses::peace() as $verse) {
        expect($verse)->toHaveKeys(['text', 'reference'])
            ->and($verse['text'])->not->toBe('')
            ->and($verse['reference'])->not->toBe('');
    }
    foreach (BibleVerses::crisis() as $verse) {
        expect($verse)->toHaveKeys(['text', 'reference'])
            ->and($verse['text'])->not->toBe('')
            ->and($verse['reference'])->not->toBe('');
    }
});

test('random returns null when feature flag is disabled', function () {
    Config::set('features.bible_verses', false);

    expect(BibleVerses::random('peace'))->toBeNull();
    expect(BibleVerses::random('crisis'))->toBeNull();
});

test('random returns a valid verse when feature flag is enabled', function () {
    Config::set('features.bible_verses', true);

    $peace = BibleVerses::random('peace');
    $crisis = BibleVerses::random('crisis');

    expect($peace)->toBeArray()->toHaveKeys(['text', 'reference']);
    expect($crisis)->toBeArray()->toHaveKeys(['text', 'reference']);

    expect(BibleVerses::peace())->toContain($peace);
    expect(BibleVerses::crisis())->toContain($crisis);
});

test('unknown situation returns null', function () {
    Config::set('features.bible_verses', true);

    expect(BibleVerses::random('something-else'))->toBeNull();
});
