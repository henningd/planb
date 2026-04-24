<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $invitation = $this->pendingInvitationFor($input['email']);

            if ($invitation !== null) {
                $this->acceptInvitation($user, $invitation);

                return $user;
            }

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }

    /**
     * Find a pending, non-expired invitation for the given email (case-insensitive).
     */
    private function pendingInvitationFor(string $email): ?TeamInvitation
    {
        return TeamInvitation::query()
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->latest('id')
            ->first();
    }

    /**
     * Accept the invitation for the newly registered user and make the team current.
     */
    private function acceptInvitation(User $user, TeamInvitation $invitation): void
    {
        $team = $invitation->team;

        $team->memberships()->create([
            'user_id' => $user->id,
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        $user->forceFill(['current_team_id' => $team->id])->save();
    }
}
