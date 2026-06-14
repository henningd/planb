<?php

use App\Mail\NewUserRegisteredMail;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('registering sends the combined welcome/verification mail and leaves the user unverified', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the verification mail uses the German welcome wording', function () {
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toContain('Willkommen')
        ->and(collect($mail->introLines)->implode(' '))->toContain('Registrierung');
});

test('a BCC notification is sent to the configured admin addresses on registration', function () {
    config(['mail.register_bcc' => ['admin@example.com', 'info@example.com']]);
    Notification::fake();
    Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(NewUserRegisteredMail::class, function (NewUserRegisteredMail $mail) {
        return $mail->recipients === ['admin@example.com', 'info@example.com']
            && $mail->user->email === 'jane@example.com';
    });
});

test('no BCC notification is sent when MAIL_REGISTER_BCC is empty', function () {
    config(['mail.register_bcc' => []]);
    Notification::fake();
    Mail::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    Mail::assertNotSent(NewUserRegisteredMail::class);
});

test('the BCC mail addresses the recipients as bcc only', function () {
    $user = User::factory()->make(['name' => 'Jane', 'email' => 'jane@example.com']);

    $envelope = (new NewUserRegisteredMail($user, ['admin@example.com']))->envelope();

    expect($envelope->bcc)->toHaveCount(1)
        ->and($envelope->bcc[0]->address)->toBe('admin@example.com')
        ->and($envelope->to)->toBe([]);
});

test('an unverified user cannot reach the dashboard and is sent to verify their email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('registering flags the new user as required to set up two-factor', function () {
    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'jane@example.com')->firstOrFail()->two_factor_required)->toBeTrue();
});

test('a new user (two_factor_required) without two-factor is forced to set it up when enforcement is on', function () {
    config(['features.enforce_two_factor' => true]);

    $user = User::factory()->create([
        'two_factor_required' => true,
        'two_factor_confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('security.edit'));
});

test('an existing user (not two_factor_required) is never forced into two-factor setup', function () {
    config(['features.enforce_two_factor' => true]);

    $user = User::factory()->create([
        'two_factor_required' => false,
        'two_factor_confirmed_at' => null,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk();
});

test('a new user with confirmed two-factor is not redirected', function () {
    config(['features.enforce_two_factor' => true]);

    $user = User::factory()->create([
        'two_factor_required' => true,
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk();
});

test('two-factor enforcement can be disabled via config', function () {
    config(['features.enforce_two_factor' => false]);

    $user = User::factory()->create(['two_factor_confirmed_at' => null]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk();
});
