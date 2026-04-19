<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Enums\Industry;
use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ServiceProvider;
use App\Models\System;
use App\Models\Team;
use App\Models\User;
use App\Scopes\CurrentCompanyScope;
use App\Support\IndustryTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Seeds a ready-to-go demo account: login as max@mustermann.de / password.
     * Idempotent – safe to re-run.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'max@mustermann.de'],
            [
                'name' => 'Max Mustermann',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $team = $user->teams()->first() ?? Team::create([
            'name' => "{$user->name}'s Team",
            'slug' => Str::slug("{$user->name}'s Team"),
            'is_personal' => true,
        ]);

        if (! $user->belongsToTeam($team)) {
            $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
        }

        if ($user->current_team_id === null) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        $company = Company::firstOrCreate(
            ['team_id' => $team->id],
            [
                'name' => 'Musterfirma GmbH',
                'industry' => Industry::Handwerk,
                'employee_count' => 9,
                'locations_count' => 1,
            ],
        );

        // Kontakte – nur einmal anlegen.
        if ($company->contacts()->count() === 0) {
            Contact::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'name' => 'Max Mustermann',
                'role' => 'Geschäftsführung',
                'phone' => '0171 1234567',
                'email' => 'max@mustermann.de',
                'type' => ContactType::Internal,
                'is_primary' => true,
            ]);

            Contact::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'name' => 'Anna Beispiel',
                'role' => 'Büroleitung',
                'phone' => '0171 2345678',
                'email' => 'anna@mustermann.de',
                'type' => ContactType::Internal,
                'is_primary' => false,
            ]);

            Contact::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'company_id' => $company->id,
                'name' => 'Peter IT-Service',
                'role' => 'IT-Dienstleister Ansprechpartner',
                'phone' => '0800 1234567',
                'email' => 'peter@it-service.example',
                'type' => ContactType::External,
                'is_primary' => false,
            ]);
        }

        // Dienstleister
        $itProvider = ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)
            ->firstOrCreate(
                ['company_id' => $company->id, 'name' => 'IT-Service GmbH'],
                [
                    'contact_name' => 'Peter Techniker',
                    'hotline' => '0800 1234567',
                    'email' => 'support@it-service.example',
                    'contract_number' => 'K-4711',
                    'sla' => 'Mo-Fr 8-18',
                    'notes' => 'Betreut Server, Netzwerk, Arbeitsplätze.',
                ],
            );

        ServiceProvider::withoutGlobalScope(CurrentCompanyScope::class)
            ->firstOrCreate(
                ['company_id' => $company->id, 'name' => 'TelCo Deutschland AG'],
                [
                    'contact_name' => 'Störungsstelle',
                    'hotline' => '0800 3300000',
                    'email' => 'stoerung@telco.example',
                    'contract_number' => 'GK-998877',
                    'sla' => '24/7',
                ],
            );

        // Systeme aus Handwerk-Vorlage laden (falls noch keine)
        if ($company->systems()->count() === 0) {
            $priorityIdByName = $company->systemPriorities()->pluck('id', 'name');
            $systems = IndustryTemplates::systemsFor(Industry::Handwerk->value) ?? [];

            foreach ($systems as $entry) {
                $system = System::withoutGlobalScope(CurrentCompanyScope::class)->create([
                    'company_id' => $company->id,
                    'name' => $entry['name'],
                    'description' => $entry['description'],
                    'category' => $entry['category'],
                    'system_priority_id' => $entry['priority']
                        ? ($priorityIdByName[$entry['priority']] ?? null)
                        : null,
                    'rto_minutes' => $entry['rto_minutes'],
                    'rpo_minutes' => $entry['rpo_minutes'],
                ]);

                if (in_array($entry['name'], ['Büro-Server / Zentralrechner', 'Handwerkersoftware', 'E-Mail'], true)) {
                    $system->serviceProviders()->syncWithoutDetaching([$itProvider->id]);
                }
            }
        }

        $this->command?->info('Demo-Daten bereit. Login: max@mustermann.de / password');
    }
}
