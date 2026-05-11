<?php

use Livewire\Livewire;

it('renders with empty value', function () {
    Livewire::test('phone-input')
        ->assertSet('value', null)
        ->assertSet('country', 'DE')
        ->assertSet('areaCode', '')
        ->assertSet('subscriber', '');
});

it('splits an existing E.164 value into country, area code and subscriber', function () {
    Livewire::test('phone-input', ['label' => 'Mobil'])
        ->set('value', '+4930123456')
        ->assertSet('country', 'DE')
        ->assertSet('areaCode', '030')
        ->assertSet('subscriber', '123456');
});

it('composes country + area code + subscriber into E.164', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('areaCode', '030')
        ->set('subscriber', '123456')
        ->assertSet('value', '+4930123456');
});

it('switches country and recomposes E.164', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('areaCode', '030')
        ->set('subscriber', '123456')
        ->assertSet('value', '+4930123456')
        ->set('country', 'AT')
        ->assertSet('value', '+4330123456');
});

it('clears value when both fields are emptied', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('areaCode', '030')
        ->set('subscriber', '123456')
        ->assertSet('value', '+4930123456')
        ->set('areaCode', '')
        ->set('subscriber', '')
        ->assertSet('value', null);
});

it('falls back to raw input when number cannot be parsed', function () {
    Livewire::test('phone-input')
        ->set('country', 'DE')
        ->set('areaCode', '')
        ->set('subscriber', 'NOT-A-NUMBER')
        ->assertSet('value', 'NOT-A-NUMBER');
});

it('splits a German mobile number into prefix and subscriber', function () {
    Livewire::test('phone-input')
        ->set('value', '+491711234567')
        ->assertSet('country', 'DE')
        ->assertSet('areaCode', '0171')
        ->assertSet('subscriber', '1234567');
});
