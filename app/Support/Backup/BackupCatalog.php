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
 *
 * `company_via`           → für Pivots/Sub-Entitäten ohne eigene company_id-
 *                           Spalte: gibt Parent-Tabelle + FK an, über die
 *                           gefiltert wird. Beim Import wird company_id NICHT
 *                           gesetzt (Spalte existiert nicht).
 *
 * `strip_on_insert[]`     → Spalten, die beim Insert weggelassen werden
 *                           (z. B. user_id-Audit-Felder, weil Users nicht
 *                           Teil des Backups sind).
 *
 * `id_remap[]`            → Map FK-Spalte → Bereich (Catalog-Key), die für
 *                           den Apply-Modus mit regenerateIds genutzt wird:
 *                           der alte Wert in der FK-Spalte wird auf die neu
 *                           generierte UUID des verlinkten Bereichs gemappt.
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
                'id_remap' => ['location_id' => 'locations', 'manager_id' => 'employees'],
            ],
            'communication_templates' => [
                'label' => 'Kommunikations-Vorlagen',
                'table' => 'communication_templates',
                'mode' => 'replace',
                'order' => 20, // nach scenarios
                'id_remap' => ['scenario_id' => 'scenarios'],
            ],
            'systems' => [
                'label' => 'Systeme',
                'table' => 'systems',
                'mode' => 'replace',
                'order' => 30, // nach priorities + emergency_levels
                'id_remap' => [
                    'system_priority_id' => 'system_priorities',
                    'emergency_level_id' => 'emergency_levels',
                ],
            ],
            'system_tasks' => [
                'label' => 'System-Aufgaben',
                'table' => 'system_tasks',
                'mode' => 'replace',
                'order' => 40, // nach systems
                'id_remap' => ['system_id' => 'systems'],
            ],
            'system_dependencies' => [
                'label' => 'System-Abhängigkeiten',
                'table' => 'system_dependencies',
                'mode' => 'replace',
                'order' => 50, // nach systems
                'company_via' => ['parent_table' => 'systems', 'fk' => 'system_id'],
                'id_remap' => ['system_id' => 'systems', 'depends_on_system_id' => 'systems'],
            ],
            'scenario_runs' => [
                'label' => 'Szenario-Übungen / Lagen',
                'table' => 'scenario_runs',
                'mode' => 'replace',
                'order' => 30, // nach scenarios
                'nested' => [
                    [
                        'table' => 'scenario_run_steps',
                        'fk' => 'scenario_run_id',
                        'strip_on_insert' => ['checked_by_user_id'],
                    ],
                ],
                'strip_on_insert' => ['started_by_user_id'],
                'id_remap' => ['scenario_id' => 'scenarios'],
            ],
            'incident_reports' => [
                'label' => 'Vorfälle (Meldepflichten)',
                'table' => 'incident_reports',
                'mode' => 'replace',
                'order' => 40, // nach scenario_runs
                'nested' => [
                    ['table' => 'incident_report_obligations', 'fk' => 'incident_report_id'],
                ],
                'id_remap' => ['scenario_run_id' => 'scenario_runs'],
            ],
            'handbook_versions' => [
                'label' => 'Notfallhandbuch-Versionen',
                'table' => 'handbook_versions',
                'mode' => 'replace',
                'order' => 30, // nach employees
                'id_remap' => [
                    'changed_by_employee_id' => 'employees',
                    'approved_by_employee_id' => 'employees',
                ],
            ],
            'handbook_tests' => [
                'label' => 'Testplan',
                'table' => 'handbook_tests',
                'mode' => 'replace',
                'order' => 30, // nach employees
                'id_remap' => ['responsible_employee_id' => 'employees'],
            ],

            // Pivots / Zuordnungen — keine eigene company_id, gefiltert über Parent.
            'pivot_employee_role' => [
                'label' => 'Zuordnung Mitarbeiter ↔ Rolle',
                'table' => 'employee_role',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'roles', 'fk' => 'role_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['role_id' => 'roles', 'employee_id' => 'employees'],
            ],
            'pivot_role_system' => [
                'label' => 'Zuordnung Rolle ↔ System (RACI)',
                'table' => 'role_system',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'roles', 'fk' => 'role_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['role_id' => 'roles', 'system_id' => 'systems'],
            ],
            'pivot_role_system_task' => [
                'label' => 'Zuordnung Rolle ↔ System-Aufgabe (RACI)',
                'table' => 'role_system_task',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'roles', 'fk' => 'role_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['role_id' => 'roles', 'system_task_id' => 'system_tasks'],
            ],
            'pivot_employee_system' => [
                'label' => 'Zuordnung Mitarbeiter ↔ System (RACI)',
                'table' => 'employee_system',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'systems', 'fk' => 'system_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['system_id' => 'systems', 'employee_id' => 'employees'],
            ],
            'pivot_system_task_employee' => [
                'label' => 'Zuordnung Mitarbeiter ↔ System-Aufgabe (RACI)',
                'table' => 'system_task_employee',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'system_tasks', 'fk' => 'system_task_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['system_task_id' => 'system_tasks', 'employee_id' => 'employees'],
            ],
            'pivot_service_provider_system' => [
                'label' => 'Zuordnung Dienstleister ↔ System (RACI)',
                'table' => 'service_provider_system',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'systems', 'fk' => 'system_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['system_id' => 'systems', 'service_provider_id' => 'service_providers'],
            ],
            'pivot_service_provider_system_task' => [
                'label' => 'Zuordnung Dienstleister ↔ System-Aufgabe (RACI)',
                'table' => 'service_provider_system_task',
                'mode' => 'replace',
                'order' => 60,
                'company_via' => ['parent_table' => 'system_tasks', 'fk' => 'system_task_id'],
                'strip_on_insert' => ['assigned_by_user_id', 'removed_by_user_id'],
                'id_remap' => ['system_task_id' => 'system_tasks', 'service_provider_id' => 'service_providers'],
            ],
        ];
    }
}
