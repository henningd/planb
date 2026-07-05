<?php

use App\Models\Company;
use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Firmenweiter Kanal für Alarmierungen (z. B. „Notfall ausgelöst"). Nur Mitglieder
// des zugehörigen Teams dürfen mithören.
Broadcast::channel('company.{company}', function (User $user, string $company) {
    $companyModel = Company::query()
        ->withoutGlobalScope(CurrentCompanyScope::class)
        ->find($company);

    if (! $companyModel) {
        return false;
    }

    return $user->teams()
        ->where('teams.id', $companyModel->team_id)
        ->exists();
});

Broadcast::channel('scenario-run.{run}', function (User $user, string $run) {
    $scenarioRun = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->find($run);

    if (! $scenarioRun) {
        return false;
    }

    return $user->teams()
        ->where('teams.id', $scenarioRun->company->team_id)
        ->exists();
});

Broadcast::channel('scenario-run.{run}.presence', function (User $user, string $run) {
    $scenarioRun = ScenarioRun::withoutGlobalScope(CurrentCompanyScope::class)->find($run);

    if (! $scenarioRun) {
        return null;
    }

    $belongs = $user->teams()
        ->where('teams.id', $scenarioRun->company->team_id)
        ->exists();

    if (! $belongs) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'initials' => $user->initials(),
    ];
});
