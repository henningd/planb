<?php

namespace App\Support\Accessibility;

/**
 * Liefert pro Severity-/Stufen-Wert ein Heroicon zusätzlich zur Farbe.
 *
 * Hintergrund: WCAG 2.1 AA, Erfolgskriterium 1.4.1 ("use of color").
 * Information darf nicht ausschließlich über Farbe transportiert werden,
 * damit auch Nutzer mit Rot-Grün-Schwäche, Graustufen-Druck oder
 * Screenreader den Zustand erkennen können.
 *
 * Die Mappings hier werden zentral verwaltet, damit Dashboard,
 * System-Listen und Audit-Log dieselben Icons benutzen.
 */
class SeverityIndicator
{
    /**
     * Heroicon-Name für eine Dashboard-Aktions-Severity
     * (overdue / today / soon / active).
     */
    public static function dashboardSeverityIcon(string $severity): string
    {
        return match ($severity) {
            'overdue' => 'exclamation-triangle',
            'today' => 'clock',
            'soon' => 'calendar-days',
            'active' => 'bell-alert',
            default => 'information-circle',
        };
    }

    /**
     * Heroicon-Name für ein Notfall-Level anhand seiner sort-Position
     * (1 = höchste Stufe, 2 = mittel, 3 = niedrig, sonst neutral).
     */
    public static function emergencyLevelIcon(int $sort): string
    {
        return match ($sort) {
            1 => 'shield-exclamation',
            2 => 'exclamation-triangle',
            3 => 'shield-check',
            default => 'shield-check',
        };
    }

    /**
     * Heroicon-Name für eine System-Prioritäts-Stufe (sort-basiert).
     */
    public static function systemPriorityIcon(int $sort): string
    {
        return match ($sort) {
            1 => 'fire',
            2 => 'exclamation-triangle',
            default => 'information-circle',
        };
    }

    /**
     * Heroicon-Name für eine Audit-Log-Aktion.
     */
    public static function auditActionIcon(string $action): string
    {
        $isAssignment = str_ends_with($action, '.assigned');
        $isUnassignment = str_ends_with($action, '.unassigned');

        return match (true) {
            $action === 'created' => 'plus-circle',
            $action === 'deleted' => 'trash',
            $isAssignment => 'link',
            $isUnassignment => 'link-slash',
            default => 'pencil-square',
        };
    }
}
