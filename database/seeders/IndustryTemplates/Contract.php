<?php

namespace Database\Seeders\IndustryTemplates;

use App\Enums\Industry;

interface Contract
{
    public function name(): string;

    public function industry(): Industry;

    public function description(): string;

    public function sort(): int;

    /**
     * Backup-kompatibles Payload mit folgender Struktur:
     *   [
     *     'version' => 2,
     *     'exported_at' => '...iso8601...',
     *     'areas' => [
     *       'company' => [ ... single record ... ],   // mode = update_single
     *       'locations' => [ ... rows ... ],
     *       'employees' => [ ... rows ... ],
     *       ...
     *     ]
     *   ]
     *
     * Bereiche orientieren sich an App\Support\Backup\BackupCatalog. Pivots
     * werden beim Apply per regenerateIds neu verknüpft (Catalog `id_remap`).
     *
     * @return array<string, mixed>
     */
    public function payload(): array;
}
