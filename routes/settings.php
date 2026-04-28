<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Models\User;
use App\Support\Privacy\AccountDataExporter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');

    Route::livewire('settings/data-privacy', 'pages::settings.data-privacy')
        ->name('settings.data-privacy');

    Route::get('settings/data-privacy/export', function (AccountDataExporter $exporter) {
        /** @var User $user */
        $user = Auth::user();

        $payload = $exporter->export($user);

        $filename = sprintf(
            'planb-account-%s-%s.json',
            $user->id,
            now()->format('Y-m-d'),
        );

        return response()->streamDownload(
            fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    })->name('settings.data-privacy.export');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');

    Route::livewire('settings/teams', 'pages::teams.index')->name('teams.index');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::livewire('settings/teams/{team}', 'pages::teams.edit')->name('teams.edit');
    });
});
