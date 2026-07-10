<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('authority_contacts')) {
            Schema::create('authority_contacts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
                $table->string('type')->default('other');
                $table->string('name');
                $table->text('occasion')->nullable();
                $table->string('deadline')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_way')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_name')->nullable();
                $table->foreignUuid('responsible_role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->foreignUuid('communication_template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'sort']);
            });
        }

        // Rückverknüpfung: eine Kommunikationsvorlage kann als Standard-Empfänger
        // einen gepflegten Behördenkontakt haben („Empfänger aus Behördenkontakten").
        if (Schema::hasTable('communication_templates')
            && ! Schema::hasColumn('communication_templates', 'recipient_authority_contact_id')) {
            Schema::table('communication_templates', function (Blueprint $table) {
                $table->foreignUuid('recipient_authority_contact_id')->nullable()
                    ->after('scenario_id')
                    ->constrained('authority_contacts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_templates') && Schema::hasColumn('communication_templates', 'recipient_authority_contact_id')) {
            Schema::table('communication_templates', function (Blueprint $table) {
                $table->dropConstrainedForeignId('recipient_authority_contact_id');
            });
        }

        Schema::dropIfExists('authority_contacts');
    }
};
