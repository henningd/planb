<?php

namespace Database\Seeders\IndustryTemplates;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Helpers
{
    public static function uuid(): string
    {
        return (string) Str::uuid();
    }

    public static function now(): string
    {
        return Carbon::now()->toDateTimeString();
    }

    public static function date(int $offsetDays = 0): string
    {
        return Carbon::now()->addDays($offsetDays)->toDateString();
    }

    /**
     * Hüllt fertige Bereiche ins Backup-Payload-Format ein.
     *
     * @param  array<string, mixed>  $areas
     * @return array<string, mixed>
     */
    public static function payload(array $areas): array
    {
        return [
            'version' => 2,
            'exported_at' => Carbon::now()->toIso8601String(),
            'areas' => $areas,
        ];
    }
}
