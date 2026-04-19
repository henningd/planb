<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::view('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

        Route::livewire('company', 'pages::company.edit')->name('company.edit');
        Route::livewire('contacts', 'pages::contacts.index')->name('contacts.index');
        Route::livewire('emergency-levels', 'pages::emergency-levels.index')->name('emergency-levels.index');
        Route::livewire('systems', 'pages::systems.index')->name('systems.index');
        Route::livewire('service-providers', 'pages::service-providers.index')->name('service-providers.index');

        Route::livewire('scenarios', 'pages::scenarios.index')->name('scenarios.index');
        Route::livewire('scenario-runs', 'pages::scenario-runs.index')->name('scenario-runs.index');
        Route::livewire('scenario-runs/{run}', 'pages::scenario-runs.show')->name('scenario-runs.show');

        Route::livewire('incidents', 'pages::incidents.index')->name('incidents.index');
        Route::livewire('incidents/{report}', 'pages::incidents.show')->name('incidents.show');

        Route::get('handbook/print', function () {
            $company = CurrentCompany::resolve();
            abort_unless($company, 404);

            $company->loadMissing([
                'contacts' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('name'),
                'emergencyLevels',
                'systems.priority',
                'systems.serviceProviders',
                'systemPriorities',
                'scenarios.steps',
            ]);

            return view('handbook-print', [
                'company' => $company,
                'providers' => \App\Models\ServiceProvider::with('systems')->orderBy('name')->get(),
            ]);
        })->name('handbook.print');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
