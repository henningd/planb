<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY = 'audit_retention_days';

    private const CAP = 360;

    /**
     * Reconcile stored per-tenant audit retention overrides with the new
     * catalog bounds (1..360 days, default 30) without silently shrinking
     * the retention of tenants that had explicitly chosen "unlimited" (0).
     *
     * A stored 0 previously meant "keep forever"; rather than dropping such
     * tenants straight to the 30 day default, we lift them to the 360 day
     * cap so they keep their history up to the new maximum. Any value above
     * the cap is clamped down to 360.
     *
     * The `value` column is JSON-encoded, so an int 0 is stored as the
     * string '0' and 360 as '360'.
     */
    public function up(): void
    {
        DB::table('company_settings')
            ->where('key', self::KEY)
            ->where('value', '0')
            ->update(['value' => (string) self::CAP, 'updated_at' => now()]);

        $rows = DB::table('company_settings')
            ->where('key', self::KEY)
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $current = (int) json_decode((string) $row->value, true);

            if ($current > self::CAP) {
                DB::table('company_settings')
                    ->where('id', $row->id)
                    ->update(['value' => (string) self::CAP, 'updated_at' => now()]);
            }
        }
    }

    /**
     * Data migration — the original per-tenant values are not recoverable
     * once capped, so the down migration is intentionally a no-op.
     */
    public function down(): void {}
};
