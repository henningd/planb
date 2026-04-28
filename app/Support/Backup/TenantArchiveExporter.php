<?php

namespace App\Support\Backup;

use App\Models\AuditLogEntry;
use App\Models\Company;
use App\Models\HandbookVersion;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Bündelt alle exportierbaren Daten eines Mandanten in ein einziges ZIP:
 *  - daten.json (alle Bereiche aus BackupCatalog)
 *  - audit-log.csv (komplette Aktivitäten-Historie)
 *  - handbook-versions/ (alle revisionssicheren PDF-Versionen)
 *  - README.txt (Stand, Mandant, Inhalt)
 *
 * Das Archiv landet im temporären Verzeichnis und wird über den Pfad
 * an den Aufrufer gegeben — der streamt es als Download und löscht es
 * danach via `unlink()` im Tap-Callback.
 */
class TenantArchiveExporter
{
    public static function export(Company $company): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'planb-archive-').'.zip';

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Konnte temporäres ZIP-Archiv nicht anlegen.');
        }

        $payload = Exporter::export($company, array_keys(BackupCatalog::all()));
        $zip->addFromString('daten.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $zip->addFromString('audit-log.csv', self::auditCsv($company));

        $disk = Storage::disk('handbook');
        $versions = HandbookVersion::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->whereNotNull('pdf_path')
            ->get();
        foreach ($versions as $version) {
            if (! $disk->exists($version->pdf_path)) {
                continue;
            }
            $contents = $disk->get($version->pdf_path);
            if ($contents === null) {
                continue;
            }
            $filename = sprintf(
                'handbook-versions/handbuch-v%s-%s.pdf',
                preg_replace('/[^A-Za-z0-9._-]+/', '-', $version->version),
                ($version->approved_at ?? $version->changed_at)->format('Y-m-d'),
            );
            $zip->addFromString($filename, $contents);
        }

        $zip->addFromString('README.txt', self::readme($company, $payload, $versions->count()));

        $zip->close();

        return $tmp;
    }

    public static function filename(Company $company): string
    {
        return sprintf(
            'planb-mandant-%s-%s.zip',
            $company->team?->slug ?? 'firma',
            now()->format('Y-m-d_His'),
        );
    }

    private static function auditCsv(Company $company): string
    {
        $entries = AuditLogEntry::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $company->id)
            ->orderBy('created_at')
            ->get();

        $rows = [];
        $rows[] = ['Zeitpunkt', 'Benutzer-ID', 'Objekt', 'Objekt-ID', 'Bezeichnung', 'Aktion', 'Änderungen'];
        foreach ($entries as $entry) {
            $rows[] = [
                $entry->created_at->format('Y-m-d H:i:s'),
                (string) ($entry->user_id ?? ''),
                $entry->entity_type,
                $entry->entity_id,
                (string) ($entry->entity_label ?? ''),
                $entry->action,
                $entry->changes !== null ? json_encode($entry->changes, JSON_UNESCAPED_UNICODE) : '',
            ];
        }

        $output = fopen('php://temp', 'r+b');
        if ($output === false) {
            return '';
        }
        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $csv = stream_get_contents($output) ?: '';
        fclose($output);

        return $csv;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function readme(Company $company, array $payload, int $pdfCount): string
    {
        $sections = isset($payload['areas']) && is_array($payload['areas'])
            ? array_keys($payload['areas'])
            : [];
        $sectionsLine = $sections === [] ? '—' : implode(', ', $sections);

        return implode("\n", [
            'PlanB · Vollständiger Mandanten-Export',
            '======================================',
            '',
            'Mandant: '.$company->name,
            'Team-Slug: '.($company->team?->slug ?? '—'),
            'Stand: '.now()->format('d.m.Y H:i:s'),
            '',
            'Inhalt dieses Archivs:',
            '  - daten.json           Alle Stammdaten (Mitarbeiter, Systeme, Aufgaben, Risiken, …) als JSON',
            '  - audit-log.csv        Komplette Aktivitäten-Historie',
            '  - handbook-versions/   '.$pdfCount.' revisionssichere Handbuch-PDF(s)',
            '',
            'Erfasste Bereiche in daten.json: '.$sectionsLine,
            '',
            'Hinweis: Diese Datei dokumentiert den Stand zum oben genannten Zeitpunkt.',
            'Sie können daten.json bei Bedarf über den Daten-Import wieder in PlanB einlesen.',
            'Die enthaltenen PDFs sind kryptografisch signiert (SHA-256 in den jeweiligen Metadaten).',
            '',
        ]);
    }
}
