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
        Schema::table('handbook_versions', function (Blueprint $table) {
            // Speichert die freigegebene PDF-Snapshot der Version (revisionssicher).
            // pdf_path liegt auf der privaten 'handbook'-Disk und ist relativ.
            $table->string('pdf_path')->nullable()->after('approved_by_name');
            $table->string('pdf_hash', 64)->nullable()->after('pdf_path');
            $table->unsignedBigInteger('pdf_size')->nullable()->after('pdf_hash');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('handbook_versions', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_hash', 'pdf_size', 'pdf_generated_at']);
        });
    }
};
