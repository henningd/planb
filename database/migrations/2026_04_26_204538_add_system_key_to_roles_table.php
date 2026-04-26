<?php

use App\Enums\CrisisRole;
use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use App\Support\SystemRoleProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Markierung für Systemrollen (vom System angelegt, nicht löschbar).
            // Hält den CrisisRole-Enum-Value (z. B. 'emergency_officer'), null
            // für vom Mandanten frei angelegte Rollen.
            $table->string('system_key', 50)->nullable()->after('name');
            // Kurzer Index-Name (MySQL 64-Char-Limit beachten).
            $table->unique(['company_id', 'system_key'], 'roles_company_system_key_unique');
        });

        // Backfill: alle bestehenden Mandanten bekommen die Systemrollen
        // direkt per DB-Insert (unabhängig von Eloquent-Events).
        Company::withoutGlobalScope(CurrentCompanyScope::class)->get()->each(function (Company $company) {
            foreach (CrisisRole::cases() as $i => $role) {
                $exists = DB::table('roles')
                    ->where('company_id', $company->id)
                    ->where(function ($q) use ($role) {
                        $q->where('system_key', $role->value)
                            ->orWhere('name', $role->label());
                    })
                    ->first();

                if ($exists !== null) {
                    if ($exists->system_key !== $role->value) {
                        DB::table('roles')->where('id', $exists->id)->update(['system_key' => $role->value]);
                    }

                    continue;
                }

                DB::table('roles')->insert([
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $role->label(),
                    'system_key' => $role->value,
                    'description' => SystemRoleProvisioner::descriptionFor($role),
                    'sort' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_company_system_key_unique');
            $table->dropColumn('system_key');
        });
    }
};
