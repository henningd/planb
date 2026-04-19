<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:promote {email : E-Mail-Adresse des Benutzers} {--demote : Superadmin-Status wieder entfernen} {--verify : Zusätzlich die E-Mail als verifiziert markieren}')]
#[Description('Setzt den Superadmin-Status für einen Benutzer (oder entfernt ihn mit --demote).')]
class PromoteToSuperAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $demote = (bool) $this->option('demote');
        $verify = (bool) $this->option('verify');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Benutzer mit E-Mail {$email} wurde nicht gefunden.");

            return self::FAILURE;
        }

        $target = ! $demote;
        $updates = [];

        if ($user->is_super_admin !== $target) {
            $updates['is_super_admin'] = $target;
        }

        if ($verify && $user->email_verified_at === null) {
            $updates['email_verified_at'] = now();
        }

        if ($updates === []) {
            $this->warn("Keine Änderung nötig für {$email}.");

            return self::SUCCESS;
        }

        $user->forceFill($updates)->save();

        if (array_key_exists('is_super_admin', $updates)) {
            $this->info($target
                ? "✓ {$email} wurde zum Superadmin befördert."
                : "✓ {$email} ist kein Superadmin mehr.");
        }

        if (array_key_exists('email_verified_at', $updates)) {
            $this->info("✓ E-Mail {$email} wurde als verifiziert markiert.");
        }

        if (! $verify && $user->fresh()->email_verified_at === null) {
            $this->warn('Hinweis: E-Mail ist noch nicht verifiziert – Admin-Bereich wird 403 liefern. Ergänze --verify oder verifiziere die Adresse im UI.');
        }

        return self::SUCCESS;
    }
}
