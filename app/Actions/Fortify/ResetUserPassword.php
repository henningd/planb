<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\Audit\AccountAudit;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();

        AccountAudit::record(
            action: 'security.password_changed',
            entityType: 'User',
            entityId: $user->id,
            entityLabel: $user->name,
            companyId: $user->currentCompany()?->id,
            actorId: $user->id,
            changes: ['via' => 'reset'],
        );
    }
}
