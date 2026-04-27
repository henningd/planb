<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['employee_system', 'service_provider_system', 'role_system'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'ownership_kind')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->string('ownership_kind', 32)->nullable()->after($this->raciAfter($table));
                });
            }
            if (! Schema::hasColumn($table, 'is_deputy')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('is_deputy')->default(false)->after('ownership_kind');
                });
            }
        }

        // Backfill: existierende RACI-Werte auf Ownership mappen.
        // A → owner (Eigentümer), R → operator (Administrator/Operator),
        // C/I → contact (fachlicher Ansprechpartner / informierte Stakeholder).
        foreach ($this->tables as $table) {
            DB::table($table)
                ->whereNull('ownership_kind')
                ->where('raci_role', 'A')
                ->update(['ownership_kind' => 'owner']);
            DB::table($table)
                ->whereNull('ownership_kind')
                ->where('raci_role', 'R')
                ->update(['ownership_kind' => 'operator']);
            DB::table($table)
                ->whereNull('ownership_kind')
                ->whereIn('raci_role', ['C', 'I'])
                ->update(['ownership_kind' => 'contact']);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'is_deputy')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('is_deputy');
                });
            }
            if (Schema::hasColumn($table, 'ownership_kind')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('ownership_kind');
                });
            }
        }
    }

    private function raciAfter(string $table): string
    {
        return match ($table) {
            'employee_system' => 'employee_id',
            'service_provider_system' => 'service_provider_id',
            'role_system' => 'role_id',
            default => 'id',
        };
    }
};
