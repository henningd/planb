<?php

namespace App\Support\Settings;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Pro-Mandant-Settings. Fallback-Kette beim Lesen:
 * 1. Mandant-Override (company_settings)
 * 2. Plattform-Default (system_settings)
 * 3. Hardcoded Catalog-Default
 *
 * Schreiben legt einen Override an. unset() entfernt ihn wieder.
 */
class CompanySetting
{
    private const CACHE_TTL = 3600;

    public function __construct(private Company $company) {}

    public static function for(Company $company): self
    {
        return new self($company);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember($this->cacheKey($key), self::CACHE_TTL, function () use ($key, $default) {
            $row = DB::table('company_settings')
                ->where('company_id', $this->company->id)
                ->where('key', $key)
                ->value('value');

            if ($row !== null) {
                return json_decode($row, true);
            }

            return SystemSetting::get($key, $default);
        });
    }

    public function set(string $key, mixed $value): void
    {
        $value = SettingsCatalog::cast($key, $value);

        DB::table('company_settings')->updateOrInsert(
            ['company_id' => $this->company->id, 'key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget($this->cacheKey($key));
    }

    public function unset(string $key): void
    {
        DB::table('company_settings')
            ->where('company_id', $this->company->id)
            ->where('key', $key)
            ->delete();

        Cache::forget($this->cacheKey($key));
    }

    public function isOverridden(string $key): bool
    {
        return DB::table('company_settings')
            ->where('company_id', $this->company->id)
            ->where('key', $key)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $rows = DB::table('company_settings')
            ->where('company_id', $this->company->id)
            ->pluck('value', 'key')
            ->all();

        $out = [];
        foreach (SettingsCatalog::byScope(SettingsCatalog::COMPANY) as $key => $def) {
            if (isset($rows[$key])) {
                $out[$key] = json_decode($rows[$key], true);
            } else {
                $out[$key] = SystemSetting::get($key, $def['default']);
            }
        }

        return $out;
    }

    private function cacheKey(string $key): string
    {
        return "company_setting:{$this->company->id}:{$key}";
    }
}
