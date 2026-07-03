<?php

use App\Enums\Nis2Readiness;
use App\Mail\Nis2QuickCheckConfirmation;
use App\Models\Lead;
use App\Support\Marketing\Nis2QuickCheckCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the public quick-check page renders without authentication', function () {
    $this->get(route('nis2-quick-check'))
        ->assertOk()
        ->assertSee('NIS2 Quick-Check');
});

test('a completed check with consent creates an unconfirmed lead and sends the double opt-in mail', function () {
    Mail::fake();

    $component = Livewire::test('nis2-quick-check');

    foreach (Nis2QuickCheckCatalog::allKeys() as $key) {
        $component->set("answers.{$key}", 'yes');
    }

    $component->call('showResult')
        ->assertSet('step', 'result')
        ->set('email', 'interessent@firma.de')
        ->set('companyName', 'Muster GmbH')
        ->set('consentReport', true)
        ->set('consentMarketing', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('step', 'done');

    $lead = Lead::query()->first();

    expect($lead)->not->toBeNull()
        ->and($lead->email)->toBe('interessent@firma.de')
        ->and($lead->company_name)->toBe('Muster GmbH')
        ->and($lead->score)->toBe(20)
        ->and($lead->readiness)->toBe(Nis2Readiness::Solide)
        ->and($lead->consent_marketing)->toBeTrue()
        ->and($lead->consent_at)->not->toBeNull()
        ->and($lead->isConfirmed())->toBeFalse();

    Mail::assertQueued(Nis2QuickCheckConfirmation::class, fn ($mail) => $mail->hasTo('interessent@firma.de'));
});

test('submitting without the required consent fails and creates no lead', function () {
    Livewire::test('nis2-quick-check')
        ->set('email', 'ohne@einwilligung.de')
        ->set('consentReport', false)
        ->call('submit')
        ->assertHasErrors(['consentReport']);

    expect(Lead::query()->count())->toBe(0);
});

test('submitting without a valid email fails', function () {
    Livewire::test('nis2-quick-check')
        ->set('email', 'keine-email')
        ->set('consentReport', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(Lead::query()->count())->toBe(0);
});

test('a filled honeypot silently discards the submission', function () {
    Livewire::test('nis2-quick-check')
        ->set('email', 'bot@spam.de')
        ->set('consentReport', true)
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Lead::query()->count())->toBe(0);
});
