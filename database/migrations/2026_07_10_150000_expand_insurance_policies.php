<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baut die Versicherungen zum vollwertigen Verträge-/Nachweis-Register aus:
     * Laufzeit, Deckungssumme, benötigte Unterlagen, zuständige Rolle,
     * Freigabe-Hinweis, Prüf-/Testtermine und Szenariobezug.
     */
    public function up(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_policies', 'coverage_amount')) {
                $table->string('coverage_amount')->nullable()->after('deductible');
            }
            if (! Schema::hasColumn('insurance_policies', 'valid_from')) {
                $table->date('valid_from')->nullable()->after('policy_number');
            }
            if (! Schema::hasColumn('insurance_policies', 'valid_until')) {
                $table->date('valid_until')->nullable()->after('valid_from');
            }
            if (! Schema::hasColumn('insurance_policies', 'required_documents')) {
                $table->text('required_documents')->nullable()->after('reporting_window');
            }
            if (! Schema::hasColumn('insurance_policies', 'responsible_role_id')) {
                $table->foreignUuid('responsible_role_id')->nullable()->after('contact_name')
                    ->constrained('roles')->nullOnDelete();
            }
            if (! Schema::hasColumn('insurance_policies', 'approval_required')) {
                $table->boolean('approval_required')->default(false)->after('required_documents');
            }
            if (! Schema::hasColumn('insurance_policies', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('approval_required');
            }
            if (! Schema::hasColumn('insurance_policies', 'claims_process_tested_at')) {
                $table->date('claims_process_tested_at')->nullable()->after('approval_note');
            }
            if (! Schema::hasColumn('insurance_policies', 'last_reviewed_at')) {
                $table->date('last_reviewed_at')->nullable()->after('claims_process_tested_at');
            }
            if (! Schema::hasColumn('insurance_policies', 'next_review_at')) {
                $table->date('next_review_at')->nullable()->after('last_reviewed_at');
            }
        });

        if (! Schema::hasTable('insurance_policy_scenario')) {
            Schema::create('insurance_policy_scenario', function (Blueprint $table) {
                $table->foreignUuid('insurance_policy_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('scenario_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['insurance_policy_id', 'scenario_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_policy_scenario');

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_role_id');
            $table->dropColumn([
                'coverage_amount', 'valid_from', 'valid_until', 'required_documents',
                'approval_required', 'approval_note', 'claims_process_tested_at',
                'last_reviewed_at', 'next_review_at',
            ]);
        });
    }
};
