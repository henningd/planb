<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Baut minimale, RFC-5545-konforme iCalendar-Einladungen (VEVENT) als String,
 * die als `.ics` an E-Mails angehängt werden. Ganztägige Termine mit optionaler
 * monatlicher Wiederholung (für wiederkehrende Prüfungen / Präventivmaßnahmen).
 */
class IcsBuilder
{
    /**
     * @param  string  $uid  stabile Kennung je Objekt, damit Kalender ein erneutes .ics als Update erkennen.
     * @param  int|null  $recurrenceMonths  Abstand in Monaten für Serientermine; null = Einzeltermin.
     */
    public static function allDayEvent(
        string $uid,
        string $summary,
        ?string $description,
        DateTimeInterface $date,
        ?int $recurrenceMonths = null,
        ?string $organizerName = null,
        ?string $organizerEmail = null,
        ?string $attendeeName = null,
        ?string $attendeeEmail = null,
    ): string {
        // Kalenderdatum ohne Zeitzonen-Umrechnung übernehmen (all-day). Der Umweg
        // über @timestamp/UTC würde ein lokales Mitternachtsdatum auf den Vortag ziehen.
        $startYmd = $date->format('Ymd');
        $endYmd = (new DateTimeImmutable($date->format('Y-m-d')))->modify('+1 day')->format('Ymd');
        $stamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//PlanB//Notfallhandbuch//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'SEQUENCE:0',
            'DTSTAMP:'.$stamp,
            'DTSTART;VALUE=DATE:'.$startYmd,
            'DTEND;VALUE=DATE:'.$endYmd,
            'SUMMARY:'.self::escape($summary),
        ];

        if ($description !== null && $description !== '') {
            $lines[] = 'DESCRIPTION:'.self::escape($description);
        }

        if ($recurrenceMonths !== null && $recurrenceMonths > 0) {
            $lines[] = 'RRULE:FREQ=MONTHLY;INTERVAL='.$recurrenceMonths;
        }

        if ($organizerEmail !== null && $organizerEmail !== '') {
            $cn = ($organizerName !== null && $organizerName !== '') ? ';CN="'.self::escapeParam($organizerName).'"' : '';
            $lines[] = 'ORGANIZER'.$cn.':mailto:'.$organizerEmail;
        }

        if ($attendeeEmail !== null && $attendeeEmail !== '') {
            $cn = ($attendeeName !== null && $attendeeName !== '') ? 'CN="'.self::escapeParam($attendeeName).'";' : '';
            $lines[] = 'ATTENDEE;'.$cn.'ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:mailto:'.$attendeeEmail;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    protected static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $value,
        );
    }

    protected static function escapeParam(string $value): string
    {
        // Anführungszeichen sind in gequoteten Parameterwerten nicht erlaubt.
        return str_replace('"', '', $value);
    }

    /**
     * Faltet Zeilen > 75 Oktette gemäß RFC 5545 (Folgezeile beginnt mit Space).
     * UTF-8-sicher: es wird nie mitten in einem Mehrbyte-Zeichen umgebrochen.
     */
    protected static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = [];
        $current = '';

        foreach ($chars as $char) {
            // Erste Zeile max. 75 Bytes; Folgezeilen tragen ein führendes Space → 74 Bytes Inhalt.
            $limit = $result === [] ? 75 : 74;

            if (strlen($current) + strlen($char) > $limit) {
                $result[] = $current;
                $current = $char;
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }

        return implode("\r\n ", $result);
    }
}
