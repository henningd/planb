<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_form')->nullable()->after('industry');
            $table->string('kritis_relevant')->nullable()->after('legal_form');
            $table->string('nis2_classification')->nullable()->after('kritis_relevant');
            $table->date('valid_from')->nullable()->after('nis2_classification');
            $table->string('cyber_insurance_deductible')->nullable()->after('valid_from');
            $table->decimal('budget_it_lead', 12, 2)->nullable()->after('cyber_insurance_deductible');
            $table->decimal('budget_emergency_officer', 12, 2)->nullable()->after('budget_it_lead');
            $table->decimal('budget_management', 12, 2)->nullable()->after('budget_emergency_officer');
            $table->string('data_protection_authority_name')->nullable()->after('budget_management');
            $table->string('data_protection_authority_phone')->nullable()->after('data_protection_authority_name');
            $table->string('data_protection_authority_website')->nullable()->after('data_protection_authority_phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_form',
                'kritis_relevant',
                'nis2_classification',
                'valid_from',
                'cyber_insurance_deductible',
                'budget_it_lead',
                'budget_emergency_officer',
                'budget_management',
                'data_protection_authority_name',
                'data_protection_authority_phone',
                'data_protection_authority_website',
            ]);
        });
    }
};
