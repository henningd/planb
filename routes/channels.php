<?php

use App\Models\ScenarioRun;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
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
