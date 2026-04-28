<?php

use App\Enums\CommunicationAudience;
use App\Enums\CommunicationChannel;
use App\Mail\CommunicationTemplateMail;
use App\Models\CommunicationDispatch;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends an email template to selected employees and logs each recipient', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'Mitarbeiter-Information',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'subject' => 'Lage bei {{firma}}',
        'body' => 'Hallo zusammen, hier ist eine Information zur aktuellen Lage bei {{firma}}.',
        'sort' => 1,
    ]);

    $employee1 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Anna',
        'last_name' => 'Müller',
        'email' => 'anna@example.com',
    ]);
    $employee2 = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'Ben',
        'last_name' => 'Schulz',
        'email' => 'ben@example.com',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('openEmailSend', $template->id)
        ->set('emailRecipients', [$employee1->id, $employee2->id])
        ->call('confirmSendEmail')
        ->call('sendEmail');

    Mail::assertSent(CommunicationTemplateMail::class, 2);

    $dispatch = CommunicationDispatch::first();
    expect($dispatch)->not->toBeNull();
    expect($dispatch->channel)->toBe('email');
    expect($dispatch->subject)->toBe('Lage bei '.$company->name);
    expect($dispatch->body)->toContain($company->name);
    expect($dispatch->recipient_count)->toBe(2);
    expect($dispatch->success_count)->toBe(2);
    expect($dispatch->failed_count)->toBe(0);
    expect($dispatch->dispatched_by_user_id)->toBe($user->id);

    $recipients = $dispatch->recipients;
    expect($recipients)->toHaveCount(2);
    expect($recipients->pluck('email')->all())->toContain('anna@example.com', 'ben@example.com');
    expect($recipients->pluck('status')->unique()->all())->toBe(['sent']);
});

it('skips email send when no recipients are selected', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'X',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'subject' => 'X',
        'body' => 'X',
        'sort' => 1,
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('openEmailSend', $template->id)
        ->set('emailRecipients', [])
        ->call('confirmSendEmail');

    Mail::assertNothingSent();
    expect(CommunicationDispatch::count())->toBe(0);
});

it('rejects sending an email if the template is not an email channel', function () {
    Mail::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'SMS-Vorlage',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Sms->value,
        'subject' => null,
        'body' => 'kurz',
        'sort' => 1,
    ]);
    $employee = Employee::withoutGlobalScope(CurrentCompanyScope::class)->create([
        'company_id' => $company->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@b.de',
    ]);

    Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('openEmailSend', $template->id)
        ->set('emailRecipients', [$employee->id])
        ->call('sendEmail');

    Mail::assertNothingSent();
    expect(CommunicationDispatch::count())->toBe(0);
});

it('shows the dispatch history for a template', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user->currentTeam)->create();

    $template = CommunicationTemplate::create([
        'company_id' => $company->id,
        'name' => 'X',
        'audience' => CommunicationAudience::Customers->value,
        'channel' => CommunicationChannel::Email->value,
        'subject' => 'X',
        'body' => 'X',
        'sort' => 1,
    ]);

    CommunicationDispatch::create([
        'company_id' => $company->id,
        'communication_template_id' => $template->id,
        'dispatched_by_user_id' => $user->id,
        'channel' => 'email',
        'subject' => 'Vergangen',
        'body' => 'Test',
        'recipient_count' => 1,
        'success_count' => 1,
        'failed_count' => 0,
        'dispatched_at' => now()->subDay(),
    ]);

    $component = Livewire\Livewire::actingAs($user->fresh())
        ->test('pages::communication-templates.index')
        ->call('openHistory', $template->id);

    expect($component->get('historyDispatches'))->toHaveCount(1);
});
