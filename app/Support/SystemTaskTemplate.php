<?php

namespace App\Support;

use App\Models\System;
use App\Models\SystemTask;

/**
 * Standard-Aufgaben-Vorlage (generischer Notfall-Ablauf) für ein System.
 * Wird auf der System-Detail- und -Bearbeiten-Seite angeboten.
 */
class SystemTaskTemplate
{
    /**
     * @return list<array{title: string, description: string}>
     */
    public static function items(): array
    {
        return [
            ['title' => __('Prüfen'), 'description' => __('Lage einschätzen: Was genau ist betroffen? Umfang und Ursache klären.')],
            ['title' => __('Sofortmaßnahme'), 'description' => __('Schaden begrenzen: kritische Daten und Geräte sichern, Betroffene informieren.')],
            ['title' => __('Eskalation'), 'description' => __('Zuständige Stellen, Dienstleister und Geschäftsführung informieren.')],
            ['title' => __('Wiederherstellung'), 'description' => __('System in definierter Reihenfolge wieder anfahren und Funktion testen.')],
            ['title' => __('Kommunikation'), 'description' => __('Mitarbeiter und – falls relevant – Kunden über den Status informieren.')],
        ];
    }

    /**
     * Legt die Vorlage-Aufgaben an – nur, wenn das System noch keine Aufgaben
     * hat (verhindert versehentliches Duplizieren). Gibt die Anzahl der neu
     * angelegten Aufgaben zurück.
     */
    public static function applyTo(System $system): int
    {
        if (SystemTask::where('system_id', $system->id)->exists()) {
            return 0;
        }

        $sort = 0;
        foreach (self::items() as $task) {
            SystemTask::create([
                'company_id' => $system->company_id,
                'system_id' => $system->id,
                'title' => $task['title'],
                'description' => $task['description'],
                'sort' => $sort++,
            ]);
        }

        return count(self::items());
    }
}
