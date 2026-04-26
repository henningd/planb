<?php

namespace App\Support\Backup;

/**
 * Definiert die Bereiche, die per Im-/Export ausgetauscht werden können.
 *
 * `mode = update_single`  → nur ein Datensatz, wird in-place aktualisiert
 *                           (für die Firma selbst — sie wird nicht gelöscht).
 * `mode = replace`        → alle Zeilen des Mandanten werden beim Import
 *                           verworfen und durch die JSON-Inhalte ersetzt.
 *
 * `order` steuert die Insert-Reihenfolge; bei `replace` läuft das DELETE
 * in absteigender Reihenfolge, das INSERT in aufsteigender.
 *
 * `nested[]` listet abhängige Tabellen, die zusammen mit dem Parent
 * exportiert / wiederhergestellt werden (z. B. scenario_steps zu scenarios).
 */
class BackupCatalog
{
    /**
     * @return array<string, array{
     *     label: string,
     *     table: string,
     *     mode: 'replace'|'update_single',
     *     order: int,
     *     nested?: list<array{table: string, fk: string}>,
     * }>
     */
    public static function all(): array
    {
        return [
            'company' => [
                'label' => 'Firma (Stammdaten)',
                'table' => 'companies',
                'mode' => 'update_single',
                'order' => 0,
            ],
            'locations' => [
                'label' => 'Standorte',
                'table' => 'locations',
                'mode' => 'replace',
                'order' => 10,
            ],
            'system_priorities' => [
                'label' => 'System-Prioritäten',
                'table' => 'system_priorities',
                'mode' => 'replace',
                'order' => 10,
            ],
            'emergency_levels' => [
                'label' => 'Notfall-Level',
                'table' => 'emergency_levels',
                'mode' => 'replace',
                'order' => 10,
            ],
            'service_providers' => [
                'label' => 'Dienstleister',
                'table' => 'service_providers',
                'mode' => 'replace',
                'order' => 10,
            ],
            'roles' => [
                'label' => 'Rollen',
                'table' => 'roles',
                'mode' => 'replace',
                'order' => 10,
            ],
            'insurance_policies' => [
                'label' => 'Versicherungen',
                'table' => 'insurance_policies',
                'mode' => 'replace',
                'order' => 10,
            ],
            'emergency_resources' => [
                'label' => 'Notfall-Ressourcen',
                'table' => 'emergency_resources',
                'mode' => 'replace',
                'order' => 10,
            ],
            'scenarios' => [
                'label' => 'Szenarien (mit Schritten)',
                'table' => 'scenarios',
                'mode' => 'replace',
                'order' => 10,
                'nested' => [
                    ['table' => 'scenario_steps', 'fk' => 'scenario_id'],
                ],
            ],
            'employees' => [
                'label' => 'Mitarbeiter',
                'table' => 'employees',
                'mode' => 'replace',
                'order' => 20, // nach locations
            ],
            'communication_templates' => [
                'label' => 'Kommunikations-Vorlagen',
                'table' => 'communication_templates',
                'mode' => 'replace',
                'order' => 20, // nach scenarios
            ],
            'systems' => [
                'label' => 'Systeme',
                'table' => 'systems',
                'mode' => 'replace',
                'order' => 30, // nach priorities + emergency_levels
            ],
            'handbook_versions' => [
                'label' => 'Notfallhandbuch-Versionen',
                'table' => 'handbook_versions',
                'mode' => 'replace',
                'order' => 30, // nach employees
            ],
            'handbook_tests' => [
                'label' => 'Testplan',
                'table' => 'handbook_tests',
                'mode' => 'replace',
                'order' => 30, // nach employees
            ],
        ];
    }
}
