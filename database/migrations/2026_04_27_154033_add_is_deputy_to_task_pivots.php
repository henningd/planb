<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['system_task_employee', 'service_provider_system_task', 'role_system_task'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'is_deputy')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('is_deputy')->default(false)->after('raci_role');
                });
            }
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
        }
    }
};
