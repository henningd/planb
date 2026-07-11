<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('handbook_versions')) {
            return;
        }

        Schema::table('handbook_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('handbook_versions', 'audit_pdf_path')) {
                $table->string('audit_pdf_path')->nullable()->after('pdf_generated_at');
            }
            if (! Schema::hasColumn('handbook_versions', 'audit_pdf_hash')) {
                $table->string('audit_pdf_hash', 64)->nullable()->after('audit_pdf_path');
            }
            if (! Schema::hasColumn('handbook_versions', 'audit_pdf_size')) {
                $table->unsignedBigInteger('audit_pdf_size')->nullable()->after('audit_pdf_hash');
            }
            if (! Schema::hasColumn('handbook_versions', 'audit_pdf_generated_at')) {
                $table->timestamp('audit_pdf_generated_at')->nullable()->after('audit_pdf_size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('handbook_versions')) {
            return;
        }

        Schema::table('handbook_versions', function (Blueprint $table) {
            foreach (['audit_pdf_path', 'audit_pdf_hash', 'audit_pdf_size', 'audit_pdf_generated_at'] as $column) {
                if (Schema::hasColumn('handbook_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
