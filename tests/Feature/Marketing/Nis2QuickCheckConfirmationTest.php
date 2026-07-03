<?php

use App\Mail\Nis2QuickCheckReport;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

function confirmUrl(Lead $lead): string
{
    return URL::signedRoute('nis2-quick-check.confirm', ['lead' => $lead->getKey()]);
}

test('a signed confirmation link confirms the lead and sends the report', function () {
    Mail::fake();

    $lead = Lead::factory()->create();

    $this->get(confirmUrl($lead))
        ->assertOk()
        ->assertSee('bestätigt');

    expect($lead->fresh()->isConfirmed())->toBeTrue()
        ->and($lead->fresh()->report_sent_at)->not->toBeNull();

    Mail::assertQueued(Nis2QuickCheckReport::class, fn ($mail) => $mail->hasTo($lead->email));
});

test('an unsigned confirmation link is rejected', function () {
    Mail::fake();

    $lead = Lead::factory()->create();

    $this->get(route('nis2-quick-check.confirm', ['lead' => $lead->getKey()]))
        ->assertForbidden();

    expect($lead->fresh()->isConfirmed())->toBeFalse();
    Mail::assertNothingQueued();
});

test('confirming twice does not send the report a second time', function () {
    Mail::fake();

    $lead = Lead::factory()->confirmed()->create();

    $this->get(confirmUrl($lead))->assertOk();

    Mail::assertNothingQueued();
});

test('the report mailable renders a real pdf attachment', function () {
    $lead = Lead::factory()->create();

    $pdf = (new Nis2QuickCheckReport($lead))->buildPdf();

    expect($pdf)->toStartWith('%PDF');
});
