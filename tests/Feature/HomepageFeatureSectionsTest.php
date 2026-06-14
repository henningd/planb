<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the homepage shows the SMS alerting section with the animated phone message', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('SMS-Alarmierung')
        ->assertSee('id="sms"', false)
        ->assertSee('PlanB Notfall')
        ->assertSee('Krisenstab aktiviert');
});

test('the homepage shows the notice/QR section with a QR code mockup', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Aushang & QR-Code', false)
        ->assertSee('id="aushang"', false)
        ->assertSee('Im Notfall hier scannen')
        ->assertSee('Beispiel-QR-Code des Notfall-Aushangs');
});
