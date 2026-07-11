<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_systems') && Schema::hasTable('risks') && ! Schema::hasTable('ai_system_risk')) {
            Schema::create('ai_system_risk', function (Blueprint $table) {
                $table->foreignUuid('ai_system_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('risk_id')->constrained()->cascadeOnDelete();
                $table->primary(['ai_system_id', 'risk_id']);
            });
        }

        if (Schema::hasTable('ai_systems') && Schema::hasTable('business_processes') && ! Schema::hasTable('ai_system_business_process')) {
            Schema::create('ai_system_business_process', function (Blueprint $table) {
                $table->foreignUuid('ai_system_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('business_process_id')->constrained()->cascadeOnDelete();
                $table->primary(['ai_system_id', 'business_process_id'], 'ai_system_business_process_primary');
            });
        }

        if (Schema::hasTable('ai_systems') && Schema::hasTable('scenarios') && ! Schema::hasTable('ai_system_scenario')) {
            Schema::create('ai_system_scenario', function (Blueprint $table) {
                $table->foreignUuid('ai_system_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('scenario_id')->constrained()->cascadeOnDelete();
                $table->primary(['ai_system_id', 'scenario_id']);
            });
        }

        // Art. 73 EU-KI-VO: meldepflichtiger schwerwiegender Vorfall am Protokolleintrag.
        if (Schema::hasTable('ai_system_log_entries')) {
            Schema::table('ai_system_log_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('ai_system_log_entries', 'reportable')) {
                    $table->boolean('reportable')->default(false)->after('summary');
                }
                if (! Schema::hasColumn('ai_system_log_entries', 'reported_at')) {
                    $table->date('reported_at')->nullable()->after('reportable');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_system_risk');
        Schema::dropIfExists('ai_system_business_process');
        Schema::dropIfExists('ai_system_scenario');

        if (Schema::hasTable('ai_system_log_entries')) {
            Schema::table('ai_system_log_entries', function (Blueprint $table) {
                if (Schema::hasColumn('ai_system_log_entries', 'reportable')) {
                    $table->dropColumn('reportable');
                }
                if (Schema::hasColumn('ai_system_log_entries', 'reported_at')) {
                    $table->dropColumn('reported_at');
                }
            });
        }
    }
};
