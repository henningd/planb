<?php

use App\Mail\KommunenInquiryReceived;
use App\Models\Lead;
use App\Support\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('a municipal inquiry is stored as a lead and notifies the platform contact', function () {
    Mail::fake();
    SystemSetting::set('platform_contact_email', 'vertrieb@arento.ai');

    Livewire::test('kommunen-kontakt')
        ->set('contactName', 'Erika Beispiel')
        ->set('organization', 'Stadt Musterstadt')
        ->set('email', 'e.beispiel@musterstadt.de')
        ->set('phone', '02241 123456')
        ->set('message', 'Bitte um ein Angebot für den Kommunal-Tarif.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSee('Vielen Dank für Ihre Anfrage');

    $lead = Lead::query()->first();
    expect($lead)->not->toBeNull()
        ->and($lead->source)->toBe('kommunen')
        ->and($lead->company_name)->toBe('Stadt Musterstadt')
        ->and($lead->answers['message'])->toContain('Kommunal-Tarif')
        ->and($lead->answers['phone'])->toBe('02241 123456');

    Mail::assertSent(KommunenInquiryReceived::class, function ($mail) {
        return $mail->hasTo('vertrieb@arento.ai');
    });
});

test('the honeypot silently swallows bot submissions', function () {
    Mail::fake();

    Livewire::test('kommunen-kontakt')
        ->set('contactName', 'Bot')
        ->set('organization', 'Bot GmbH')
        ->set('email', 'bot@example.com')
        ->set('message', 'Spam')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Lead::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('required fields are validated', function () {
    Livewire::test('kommunen-kontakt')
        ->call('submit')
        ->assertHasErrors(['contactName' => 'required', 'organization' => 'required', 'email' => 'required', 'message' => 'required']);
});

test('a missing platform contact email never loses the inquiry', function () {
    Mail::fake();
    // Keine Kontakt-Adresse hinterlegt — der Lead muss trotzdem entstehen.
    SystemSetting::set('platform_contact_email', '');

    Livewire::test('kommunen-kontakt')
        ->set('contactName', 'Erika Beispiel')
        ->set('organization', 'Gemeinde Test')
        ->set('email', 'e@test.de')
        ->set('message', 'Demo bitte.')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Lead::query()->count())->toBe(1);
    Mail::assertNothingSent();
});

test('the kommunen page renders the contact form and pricing links to it', function () {
    $this->get(route('kommunen.show'))
        ->assertOk()
        ->assertSee('Angebot oder Demo anfragen')
        ->assertSee('Anfrage senden');

    $this->get(route('pricing.show'))
        ->assertOk()
        ->assertSee(route('kommunen.show').'#kontakt', false);
});
