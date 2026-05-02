<?php

use App\Support\DataProtectionAuthorities;
use Database\Seeders\DataProtectionAuthoritiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DataProtectionAuthoritiesSeeder::class);
});

test('PLZ Stuttgart → LfDI Baden-Württemberg', function () {
    $auth = DataProtectionAuthorities::resolveByPostalCode('70173');
    expect($auth)->not->toBeNull()
        ->and($auth->key)->toBe('lfdi-bw');
});

test('PLZ München → BayLDA', function () {
    $auth = DataProtectionAuthorities::resolveByPostalCode('80331');
    expect($auth?->key)->toBe('baylda');
});

test('PLZ Berlin Mitte → BlnBDI', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('10115')?->key)->toBe('blnbdi');
});

test('PLZ Potsdam → LDA Brandenburg', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('14467')?->key)->toBe('lda-bb');
});

test('PLZ Cottbus → LDA Brandenburg', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('03046')?->key)->toBe('lda-bb');
});

test('PLZ Hamburg-Stadt → HmbBfDI', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('20095')?->key)->toBe('hmbbfdi');
});

test('PLZ Köln → LDI NRW', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('50667')?->key)->toBe('ldi-nrw');
});

test('PLZ Frankfurt → HBDI', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('60311')?->key)->toBe('hbdi');
});

test('PLZ Mainz → LfDI RP', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('55116')?->key)->toBe('lfdi-rp');
});

test('PLZ Saarbrücken → UDS', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('66111')?->key)->toBe('uld-sl');
});

test('PLZ Dresden → SDTB Sachsen', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('01067')?->key)->toBe('sdb');
});

test('PLZ Erfurt → TLfDI Thüringen', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('99084')?->key)->toBe('tlfdi');
});

test('PLZ Bremen-Stadt → LfDI Bremen', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('28195')?->key)->toBe('lfdi-hb');
});

test('PLZ Hannover → LfD Niedersachsen', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('30159')?->key)->toBe('lfd-ni');
});

test('PLZ Kiel → ULD Schleswig-Holstein', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('24103')?->key)->toBe('uld-sh');
});

test('PLZ Magdeburg → LfD Sachsen-Anhalt', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('39104')?->key)->toBe('lfd-st');
});

test('PLZ Schwerin → LfDI MV', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode('19053')?->key)->toBe('lfd-mv');
});

test('ungültige PLZ liefert null', function () {
    expect(DataProtectionAuthorities::resolveByPostalCode(null))->toBeNull()
        ->and(DataProtectionAuthorities::resolveByPostalCode(''))->toBeNull()
        ->and(DataProtectionAuthorities::resolveByPostalCode('abc'))->toBeNull()
        ->and(DataProtectionAuthorities::resolveByPostalCode('123'))->toBeNull()
        ->and(DataProtectionAuthorities::resolveByPostalCode('123456'))->toBeNull();
});

test('PLZ aus Lücke (kein Bereich passt) liefert null', function () {
    // Z. B. 99999 fällt in Thüringen, aber 76900 könnte in einer Lücke sein.
    // Wir testen mit einer wirklich nicht abgedeckten PLZ.
    $result = DataProtectionAuthorities::resolveByPostalCode('00000');
    expect($result)->toBeNull();
});

test('PLZ-Eingabe wird zu 5 Ziffern normalisiert (mit Leerzeichen)', function () {
    expect(DataProtectionAuthorities::normalize(' 70173 '))->toBe('70173')
        ->and(DataProtectionAuthorities::normalize('D-70173'))->toBe('70173')
        ->and(DataProtectionAuthorities::normalize('123'))->toBeNull();
});
