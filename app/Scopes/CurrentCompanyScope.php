<?php

namespace App\Scopes;

use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CurrentCompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = CurrentCompany::id();

        if ($companyId !== null) {
            $builder->where($model->qualifyColumn('company_id'), $companyId);

            return;
        }

        // Kein Company-Kontext: Ist trotzdem ein regulärer Nutzer angemeldet
        // (z. B. frisch registriert, Firmenprofil noch nicht angelegt), darf er
        // KEINE Mandantendaten sehen – Deny-by-default gegen Daten-Leaks. Ohne
        // diesen Riegel würde die Query alle Datensätze aller Mandanten liefern.
        //
        // Bewusst ausgenommen (bleiben ungescoped):
        //  - Konsole/Seeder/Jobs: kein angemeldeter Nutzer.
        //  - Super-Admins: plattformweiter Zugriff per Design.
        $user = Auth::user();

        if ($user instanceof User && ! $user->isSuperAdmin()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
