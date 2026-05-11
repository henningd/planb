<?php

use Livewire\Livewire;

it('renders with empty value', function () {
    Livewire::test('phone-input')
        ->assertSet('value', null)
        ->assertSet('country', 'DE')
        ->assertSet('national', '');
});

it('parses an existing E.164 value into country and national parts', function () {
    Livewire::test('phone-input', ['label' => 'Mobil'])
        ->set('value', '+4930123456')
        ->assertSet('country', 'DE')
        ->assertSet('national', '030 123456');
});

it('composes country plus national into E.164', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('national', '030 123456')
        ->assertSet('value', '+4930123456');
});

it('switches country and recomposes E.164', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('national', '30123456')
        ->assertSet('value', '+4930123456')
        ->set('country', 'AT')
        ->assertSet('value', '+4330123456');
});

it('clears value when national is emptied', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('national', '030 123456')
        ->assertSet('value', '+4930123456')
        ->set('national', '')
        ->assertSet('value', null);
});

it('falls back to raw input when number cannot be parsed', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('national', 'NOT-A-NUMBER')
        ->assertSet('value', 'NOT-A-NUMBER');
});
