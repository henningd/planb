<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:promote {email : E-Mail-Adresse des Benutzers} {--demote : Superadmin-Status wieder entfernen}')]
#[Description('Setzt den Superadmin-Status für einen Benutzer (oder entfernt ihn mit --demote).')]
class PromoteToSuperAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $demote = (bool) $this->option('demote');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Benutzer mit E-Mail {$email} wurde nicht gefunden.");

            return self::FAILURE;
        }

        $target = ! $demote;

        if ($user->is_super_admin === $target) {
            $this->warn("Benutzer {$email} ist bereits ".($target ? 'Superadmin' : 'kein Superadmin').' – nichts zu tun.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_super_admin' => $target])->save();

        $this->info(
            $target
                ? "✓ {$email} wurde zum Superadmin befördert."
                : "✓ {$email} ist kein Superadmin mehr."
        );

        return self::SUCCESS;
    }
}
