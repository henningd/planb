<?php

namespace App\Support\Push;

/**
 * Versendet Push-Nachrichten an Endgeräte der Notfall-App.
 *
 * Implementierungen: {@see LogPushSender} (Default ohne Firebase, protokolliert
 * nur) und {@see FcmPushSender} (echter Versand via FCM HTTP v1, sobald
 * Firebase konfiguriert ist). Gebunden im AppServiceProvider je nach Config.
 */
interface PushSender
{
    /**
     * @param  list<string>  $tokens  FCM-Registration-Tokens der Zielgeräte.
     * @param  array<string, string>  $data  Data-Payload (z. B. ['type' => 'sync']).
     * @param  string|null  $title  Optionaler Benachrichtigungstitel (sichtbare Alarmierung).
     * @param  string|null  $body  Optionaler Benachrichtigungstext.
     * @return list<string> Ungültige (z. B. abgemeldete) Tokens, die aufgeräumt werden sollten.
     */
    public function send(array $tokens, array $data, ?string $title = null, ?string $body = null): array;
}
