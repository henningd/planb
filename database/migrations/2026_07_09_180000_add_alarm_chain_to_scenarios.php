<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent: ein abgebrochener früherer Lauf kann Spalten bereits
        // hinzugefügt haben, ohne die Migration zu registrieren.
        if (Schema::hasColumn('scenarios', 'alarm_chain_detector')) {
            return;
        }

        Schema::table('scenarios', function (Blueprint $table) {
            $table->text('alarm_chain_detector')->nullable()->after('trigger');
            $table->text('alarm_chain_first_contact')->nullable()->after('alarm_chain_detector');
            $table->text('alarm_chain_lead_role')->nullable()->after('alarm_chain_first_contact');
            $table->text('alarm_chain_providers')->nullable()->after('alarm_chain_lead_role');
            $table->text('alarm_chain_management')->nullable()->after('alarm_chain_providers');
            $table->text('alarm_chain_authorities')->nullable()->after('alarm_chain_management');
            $table->text('alarm_chain_comms_approval')->nullable()->after('alarm_chain_authorities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn([
                'alarm_chain_detector',
                'alarm_chain_first_contact',
                'alarm_chain_lead_role',
                'alarm_chain_providers',
                'alarm_chain_management',
                'alarm_chain_authorities',
                'alarm_chain_comms_approval',
            ]);
        });
    }
};
