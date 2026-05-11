<?php

use App\Support\PhoneFormat;

it('returns empty string for blank input', function () {
    expect(PhoneFormat::display(null))->toBe('');
    expect(PhoneFormat::display(''))->toBe('');
    expect(PhoneFormat::tel(null))->toBe('');
});

it('formats German E.164 number as national display', function () {
    expect(PhoneFormat::display('+4930123456'))->toBe('030 123456');
});

it('formats foreign E.164 number as international display', function () {
    expect(PhoneFormat::display('+33142685300'))->toBe('+33 1 42 68 53 00');
});

it('returns E.164 for tel: link', function () {
    expect(PhoneFormat::tel('+4930123456'))->toBe('+4930123456');
});

it('falls back to raw value when number cannot be parsed', function () {
    $weird = 'Mr. Phone';
    expect(PhoneFormat::display($weird))->toBe($weird);
    expect(PhoneFormat::tel($weird))->toBe($weird);
});

it('parses bare German number with default region', function () {
    expect(PhoneFormat::display('030 12345', 'DE'))->toBe('030 12345');
    expect(PhoneFormat::tel('030 123456', 'DE'))->toBe('+4930123456');
});
