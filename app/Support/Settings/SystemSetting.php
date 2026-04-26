<?php

namespace App\Support\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Plattformweite Settings (key/value, JSON-serialisiert). 1h-Cache,
 * Schreiben invalidiert. Greift kaskadierend mit dem hartcodierten
 * Default aus dem SettingsCatalog, wenn nichts gespeichert ist.
 */
class SystemSetting
{
    private const CACHE_TTL = 3600;

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::cacheKey($key), self::CACHE_TTL, function () use ($key, $default) {
            $row = DB::table('system_settings')->where('key', $key)->value('value');
            if ($row === null) {
                return $default ?? SettingsCatalog::defaultFor($key);
            }

            return json_decode($row, true);
        });
    }

    public static function set(string $key, mixed $value): void
    {
        $value = SettingsCatalog::cast($key, $value);

        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget(self::cacheKey($key));
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $rows = DB::table('system_settings')->pluck('value', 'key')->all();
        $out = [];
        foreach (SettingsCatalog::byScope(SettingsCatalog::SYSTEM) as $key => $def) {
            $out[$key] = isset($rows[$key]) ? json_decode($rows[$key], true) : $def['default'];
        }

        return $out;
    }

    private static function cacheKey(string $key): string
    {
        return "system_setting:{$key}";
    }
}
