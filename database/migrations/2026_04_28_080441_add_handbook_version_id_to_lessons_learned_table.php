<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons_learned', function (Blueprint $table) {
            $table->foreignUuid('handbook_version_id')->nullable()->after('scenario_run_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons_learned', function (Blueprint $table) {
            $table->dropForeign(['handbook_version_id']);
            $table->dropColumn('handbook_version_id');
        });
    }
};
