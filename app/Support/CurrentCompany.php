<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the currently active Company based on the authenticated user's current team.
 */
class CurrentCompany
{
    public static function id(): ?string
    {
        return self::resolve()?->id;
    }

    public static function resolve(): ?Company
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->currentCompany();
    }
}
