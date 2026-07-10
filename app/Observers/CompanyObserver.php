<?php

namespace App\Observers;

use App\Models\AuthorityContact;
use App\Models\Company;
use App\Models\EmergencyResourceCategory;
use App\Models\GlobalScenario;
use App\Support\Audit\AccountAudit;
use App\Support\SystemRoleProvisioner;

class CompanyObserver
{
    /**
     * @var array<int, array{name: string, description: string, reaction: string}>
     */
    protected const DEFAULT_LEVELS = [
        [
            'name' => 'Kritisch',
            'description' => 'Betrieb steht oder ist massiv eingeschränkt. Kunden, Umsatz oder Sicherheit sind direkt betroffen.',
            'reaction' => 'Geschäftsführung und Krisenstab sofort informieren. Höchste Priorität für Wiederherstellung und Kommunikation.',
        ],
        [
            'name' => 'Wichtig',
            'description' => 'Einzelne Systeme oder Prozesse ausgefallen. Tagesgeschäft ist eingeschränkt, aber nicht vollständig blockiert.',
            'reaction' => 'Verantwortliche Fachbereiche benachrichtigen. Wiederanlauf innerhalb definierter Zeit einleiten.',
        ],
        [
            'name' => 'Beobachten',
            'description' => 'Auffälligkeiten oder Warnsignale, die das Tagesgeschäft aktuell nicht beeinträchtigen.',
            'reaction' => 'Lage dokumentieren, beobachten und bei Eskalation in ein höheres Level überführen.',
        ],
    ];

    /**
     * @var array<int, array{name: string, description: string}>
     */
    protected const DEFAULT_SYSTEM_PRIORITIES = [
        ['name' => 'Kritisch', 'description' => 'Geschäftsbetrieb steht ohne dieses System still. Muss zuerst wiederhergestellt werden.'],
        ['name' => 'Hoch', 'description' => 'Wichtig für den Alltag, aber für begrenzte Zeit kompensierbar.'],
        ['name' => 'Normal', 'description' => 'Kann nachgelagert wiederhergestellt werden.'],
    ];

    /**
     * Seed emergency levels, system priorities and – from the global library
     * maintained by super admins – all active scenarios for every new company.
     */
    public function created(Company $company): void
    {
        foreach (self::DEFAULT_LEVELS as $index => $level) {
            $company->emergencyLevels()->create([
                ...$level,
                'sort' => $index + 1,
            ]);
        }

        foreach (self::DEFAULT_SYSTEM_PRIORITIES as $index => $priority) {
            $company->systemPriorities()->create([
                ...$priority,
                'sort' => $index + 1,
            ]);
        }

        foreach (EmergencyResourceCategory::defaultNames() as $index => $name) {
            $company->emergencyResourceCategories()->create([
                'name' => $name,
                'sort' => $index + 1,
            ]);
        }

        foreach (AuthorityContact::defaultsForIndustry($company->industry) as $index => $contact) {
            $company->authorityContacts()->create([
                'type' => $contact['type'],
                'name' => $contact['name'],
                'occasion' => $contact['occasion'],
                'deadline' => $contact['deadline'],
                'sort' => $index + 1,
            ]);
        }

        $globalScenarios = GlobalScenario::where('is_active', true)
            ->with('steps')
            ->orderBy('sort')
            ->get();

        foreach ($globalScenarios as $global) {
            $scenario = $company->scenarios()->create([
                'name' => $global->name,
                'description' => $global->description,
                'trigger' => $global->trigger,
            ]);

            foreach ($global->steps as $step) {
                $scenario->steps()->create([
                    'sort' => $step->sort,
                    'title' => $step->title,
                    'description' => $step->description,
                    'responsible' => $step->responsible,
                ]);
            }
        }

        SystemRoleProvisioner::ensureFor($company);

        AccountAudit::record(
            action: 'created',
            entityType: 'Company',
            entityId: $company->id,
            entityLabel: $company->name,
            companyId: $company->id,
        );
    }

    /**
     * Logs renames of the company name / display name into the audit trail.
     */
    public function updated(Company $company): void
    {
        $changes = [];

        foreach (['name', 'display_name'] as $field) {
            if ($company->wasChanged($field)) {
                $changes[$field] = [
                    'old' => $company->getOriginal($field),
                    'new' => $company->{$field},
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        AccountAudit::record(
            action: 'updated',
            entityType: 'Company',
            entityId: $company->id,
            entityLabel: $company->name,
            companyId: $company->id,
            changes: $changes,
        );
    }
}
